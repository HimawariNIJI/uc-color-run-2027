<?php

namespace Tests\Fakes;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * An in-memory Google Sheets for tests: answers the service account sign-in (checking the JWT
 * signature with a throwaway key) and the Sheets API calls the app makes, keeping a real grid of
 * values. Writes past the grid's columns are refused like Google does. Nothing leaves the machine.
 */
final class FakeGoogleSheets
{
    public const SPREADSHEET_ID = 'test-spreadsheet-1234567890abcdefghij';

    public const TOKEN_URI = 'https://oauth2.googleapis.com/token';

    /**
     * @var array<string, array{sheetId: int, rowCount: int, columnCount: int, frozenRowCount: int, hasFilter: bool, rows: list<list<mixed>>}>
     */
    public array $tabs = [];

    /**
     * Every Sheets API call: method, path (decoded), query string and JSON body.
     *
     * @var list<array{method: string, path: string, query: array<string, mixed>, body: array<string, mixed>}>
     */
    public array $calls = [];

    public int $tokenRequests = 0;

    /**
     * Answer sign-ins like Google does for a revoked or wrong key.
     */
    public bool $refuseSignIn = false;

    /**
     * HTTP statuses the next Sheets API calls answer with, in order (0: the connection is lost).
     *
     * @var list<int>
     */
    private array $failures = [];

    private static ?string $privateKey = null;

    private static ?string $publicKey = null;

    /**
     * Configure the app for a fake spreadsheet (with or without a Participants tab) and fake Google.
     */
    public static function install(bool $withParticipantsTab = false): self
    {
        $fake = new self;

        if ($withParticipantsTab) {
            $fake->addTab('Participants');
        }

        $credentials = tempnam(sys_get_temp_dir(), 'sa').'.json';
        file_put_contents($credentials, json_encode([
            'type' => 'service_account',
            'project_id' => 'uc-color-run-test',
            'private_key_id' => 'test',
            'private_key' => self::privateKey(),
            'client_email' => 'sheets@uc-color-run-test.iam.gserviceaccount.com',
            'token_uri' => self::TOKEN_URI,
        ]));

        config(['services.google_sheets.spreadsheet_id' => self::SPREADSHEET_ID, 'services.google_sheets.credentials' => $credentials]);

        Http::fake([
            self::TOKEN_URI => fn (Request $request) => $fake->token($request),
            'sheets.googleapis.com/*' => fn (Request $request) => $fake->sheets($request),
        ]);

        return $fake;
    }

    /**
     * A tab as a user would create it: Google's default 1000 rows by 26 columns (A to Z).
     *
     * @param  list<list<mixed>>  $rows
     */
    public function addTab(string $title, array $rows = []): void
    {
        $this->tabs[$title] = ['sheetId' => count($this->tabs) + 100, 'rowCount' => 1000, 'columnCount' => 26, 'frozenRowCount' => 0, 'hasFilter' => false, 'rows' => $rows];
    }

    /**
     * The Participants rows below the header, keyed by column header.
     *
     * @return list<array<string, mixed>>
     */
    public function participants(): array
    {
        $rows = $this->tabs['Participants']['rows'] ?? [];
        $headers = $rows[0] ?? [];

        return collect($rows)->skip(1)
            ->filter(fn (array $row): bool => collect($row)->contains(fn (mixed $value): bool => $value !== '' && $value !== null))
            ->map(fn (array $row): array => array_combine($headers, array_pad(array_slice($row, 0, count($headers)), count($headers), '')))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function participant(string $registrationId): array
    {
        return collect($this->participants())->firstWhere('REGISTRATION ID', $registrationId)
            ?? throw new RuntimeException("No row for {$registrationId}.");
    }

    public function failNext(int $status, int $times = 1): void
    {
        array_push($this->failures, ...array_fill(0, $times, $status));
    }

    public function loseConnectionNext(): void
    {
        $this->failures[] = 0;
    }

    /**
     * Google is back: forget the failures still waiting.
     */
    public function recover(): void
    {
        $this->failures = [];
    }

    /**
     * @return list<array{method: string, path: string, body: array<string, mixed>}>
     */
    public function writes(): array
    {
        return array_values(array_filter($this->calls, fn (array $call): bool => $call['method'] === 'POST'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function batchRequests(): array
    {
        return collect($this->calls)->filter(fn (array $call): bool => str_ends_with($call['path'], ':batchUpdate') && ! str_contains($call['path'], 'values'))
            ->flatMap(fn (array $call): array => $call['body']['requests'] ?? [])
            ->values()
            ->all();
    }

    private function token(Request $request): mixed
    {
        $this->tokenRequests++;
        [$header, $claims, $signature] = explode('.', (string) $request['assertion']) + [null, null, null];
        $decode = fn (?string $part): string => (string) base64_decode(strtr((string) $part, '-_', '+/'));
        $payload = json_decode($decode($claims), true);

        $valid = ! $this->refuseSignIn
            && $request['grant_type'] === 'urn:ietf:params:oauth:grant-type:jwt-bearer'
            && json_decode($decode($header), true) === ['alg' => 'RS256', 'typ' => 'JWT']
            && ($payload['scope'] ?? null) === 'https://www.googleapis.com/auth/spreadsheets'
            && ($payload['aud'] ?? null) === self::TOKEN_URI
            && openssl_verify("{$header}.{$claims}", $decode($signature), self::$publicKey, OPENSSL_ALGO_SHA256) === 1;

        return $valid
            ? Http::response(['access_token' => 'fake-token-'.Str::random(8), 'expires_in' => 3599, 'token_type' => 'Bearer'])
            : Http::response(['error' => 'invalid_grant', 'error_description' => 'Invalid JWT Signature.'], 400);
    }

    private function sheets(Request $request): mixed
    {
        $base = 'https://sheets.googleapis.com/v4/spreadsheets/'.self::SPREADSHEET_ID;
        $path = rawurldecode(Str::after(strtok($request->url(), '?') ?: '', $base));
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
        $body = json_decode($request->body(), true) ?? [];
        $this->calls[] = ['method' => $request->method(), 'path' => $path, 'query' => $query, 'body' => $body];

        if (! str_starts_with($request->url(), $base) || ! str_starts_with((string) $request->header('Authorization')[0], 'Bearer fake-token-')) {
            return Http::response(['error' => ['code' => 403, 'message' => 'The caller does not have permission', 'status' => 'PERMISSION_DENIED']], 403);
        }

        $status = array_shift($this->failures);

        if ($status === 0) {
            return (Http::failedConnection('cURL error 28: Operation timed out'))($request);
        }

        if ($status !== null) {
            return Http::response(['error' => ['code' => $status, 'message' => 'Simulated Google error', 'status' => 'UNAVAILABLE']], $status);
        }

        return match (true) {
            $request->method() === 'GET' && $path === '' => Http::response(['sheets' => collect($this->tabs)->map(fn (array $tab, string $title): array => array_filter([
                'properties' => ['sheetId' => $tab['sheetId'], 'title' => $title, 'gridProperties' => ['rowCount' => $tab['rowCount'], 'columnCount' => $tab['columnCount']]],
                'basicFilter' => $tab['hasFilter'] ? ['range' => ['sheetId' => $tab['sheetId']]] : null,
            ]))->values()->all()]),
            $path === ':batchUpdate' => $this->structure($body['requests']),
            $path === '/values:batchUpdate' => $this->write($body),
            str_starts_with($path, '/values/') => $this->read(Str::after($path, '/values/')),
            default => Http::response(['error' => ['code' => 404, 'message' => 'Not found']], 404),
        };
    }

    /**
     * @param  list<array<string, mixed>>  $requests
     */
    private function structure(array $requests): mixed
    {
        foreach ($requests as $request) {
            $title = $this->titleOf($request);

            match (array_key_first($request)) {
                'addSheet' => $this->tabs[$request['addSheet']['properties']['title']] = [
                    'sheetId' => count($this->tabs) + 100,
                    'rowCount' => $request['addSheet']['properties']['gridProperties']['rowCount'] ?? 1000,
                    'columnCount' => $request['addSheet']['properties']['gridProperties']['columnCount'] ?? 26,
                    'frozenRowCount' => $request['addSheet']['properties']['gridProperties']['frozenRowCount'] ?? 0,
                    'hasFilter' => false,
                    'rows' => [],
                ],
                'updateSheetProperties' => $this->tabs[$title] = array_merge($this->tabs[$title], $request['updateSheetProperties']['properties']['gridProperties']),
                'insertDimension' => array_splice($this->tabs[$title]['rows'], $request['insertDimension']['range']['startIndex'], 0, [[]]),
                'setBasicFilter' => $this->tabs[$title]['hasFilter'] = true,
                'appendCells' => $this->appendCells($title, $request['appendCells']['rows']),
                default => null,
            };
        }

        return Http::response(['replies' => []]);
    }

    /**
     * @param  array<string, mixed>  $request
     */
    private function titleOf(array $request): ?string
    {
        foreach (['*.properties.sheetId', '*.range.sheetId', '*.filter.range.sheetId', '*.sheetId'] as $path) {
            $ids = array_values(array_filter((array) data_get($request, $path)));

            if ($ids !== []) {
                return collect($this->tabs)->search(fn (array $tab): bool => $tab['sheetId'] === $ids[0]) ?: null;
            }
        }

        return null;
    }

    private function read(string $range): mixed
    {
        [$title, $startColumn, $startRow, $endColumn, $endRow] = $this->parse($range);

        if (! isset($this->tabs[$title])) {
            return Http::response(['error' => ['code' => 400, 'message' => "Unable to parse range: {$range}", 'status' => 'INVALID_ARGUMENT']], 400);
        }

        $rows = [];

        foreach (array_slice($this->tabs[$title]['rows'], $startRow, $endRow === null ? null : $endRow - $startRow + 1) as $row) {
            $cells = array_slice(array_pad($row, $endColumn + 1, ''), $startColumn, $endColumn - $startColumn + 1);

            while ($cells !== [] && end($cells) === '') {
                array_pop($cells);
            }

            $rows[] = array_map(fn (mixed $value): string => (string) $value, $cells);
        }

        while ($rows !== [] && end($rows) === []) {
            array_pop($rows);
        }

        return Http::response(['range' => $range, 'values' => $rows]);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function write(array $body): mixed
    {
        foreach ($body['data'] as $data) {
            [$title, $startColumn, $startRow] = $this->parse($data['range']);

            if (! isset($this->tabs[$title])) {
                return Http::response(['error' => ['code' => 400, 'message' => 'Unable to parse range']], 400);
            }

            foreach ($data['values'] as $offset => $values) {
                if ($startColumn + count($values) > $this->tabs[$title]['columnCount']) {
                    return Http::response(['error' => ['code' => 400, 'message' => "Range ({$data['range']}) exceeds grid limits. Max columns: {$this->tabs[$title]['columnCount']}"]], 400);
                }

                $row = array_pad($this->tabs[$title]['rows'][$startRow + $offset] ?? [], $startColumn, '');
                array_splice($row, $startColumn, count($values), $values);
                $this->tabs[$title]['rows'] = array_pad($this->tabs[$title]['rows'], $startRow + $offset, []);
                $this->tabs[$title]['rows'][$startRow + $offset] = $row;
            }
        }

        return Http::response(['totalUpdatedRows' => count($body['data'])]);
    }

    /**
     * Rows written below the last row with data, like Google's appendCells.
     *
     * @param  list<array{values: list<array<string, mixed>>}>  $rows
     */
    private function appendCells(string $title, array $rows): void
    {
        $existing = $this->tabs[$title]['rows'];

        while ($existing !== [] && collect(end($existing))->every(fn (mixed $value): bool => $value === '' || $value === null)) {
            array_pop($existing);
        }

        foreach ($rows as $row) {
            $existing[] = array_map(fn (array $cell): mixed => $cell['userEnteredValue']['stringValue'] ?? $cell['userEnteredValue']['numberValue'] ?? '', $row['values']);
        }

        $this->tabs[$title]['rows'] = $existing;
    }

    /**
     * "'Participants'!A2:AD2" into title, 0-based start column and row, end column and row (null: open).
     *
     * @return array{0: string, 1: int, 2: int, 3: int, 4: ?int}
     */
    private function parse(string $range): array
    {
        preg_match("/^'(.+)'!([A-Z]+)(\d*)(?::([A-Z]+)(\d*))?$/", $range, $match);
        $column = fn (string $letters): int => array_reduce(str_split($letters), fn (int $carry, string $letter): int => $carry * 26 + ord($letter) - 64, 0) - 1;

        return [
            $match[1],
            $column($match[2]),
            $match[3] === '' ? 0 : (int) $match[3] - 1,
            $column($match[4] ?? $match[2]),
            ($match[5] ?? '') === '' ? ($match[3] === '' ? null : (int) $match[3] - 1) : (int) $match[5] - 1,
        ];
    }

    /**
     * A throwaway RSA key, made once per test run (Windows PHP needs its bundled openssl.cnf).
     */
    private static function privateKey(): string
    {
        if (self::$privateKey === null) {
            $options = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
            $bundledConfig = dirname(PHP_BINARY).'/extras/ssl/openssl.cnf';

            if (is_file($bundledConfig)) {
                $options['config'] = $bundledConfig;
            }

            $key = openssl_pkey_new($options) ?: throw new RuntimeException('Could not create a test RSA key.');
            openssl_pkey_export($key, $privateKey, null, $options);
            self::$privateKey = $privateKey;
            self::$publicKey = openssl_pkey_get_details($key)['key'];
        }

        return self::$privateKey;
    }
}
