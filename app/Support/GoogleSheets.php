<?php

namespace App\Support;

use App\Exceptions\GoogleSheetsException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * A small Google Sheets API v4 client for one spreadsheet, signed in as a service account.
 *
 * The service account signs a short-lived JWT with its private key (RS256) and exchanges it for an
 * access token, which is cached until shortly before it expires. Only the endpoints the
 * Participants sheet needs are wrapped. Values are written RAW, so text a participant typed (for
 * example a name starting with "=") is never run as a formula. Nothing here logs the key, the token
 * or the values sent.
 */
final class GoogleSheets
{
    private const API = 'https://sheets.googleapis.com/v4/spreadsheets/';

    private const SCOPE = 'https://www.googleapis.com/auth/spreadsheets';

    private const TIMEOUT_SECONDS = 20;

    /**
     * @param  array{client_email: string, private_key: string, token_uri: string}  $credentials
     */
    public function __construct(private readonly string $spreadsheetId, private readonly array $credentials) {}

    /**
     * The client for the configured spreadsheet.
     *
     * @throws GoogleSheetsException when the configuration is incomplete or the key cannot be used
     */
    public static function fromConfig(): self
    {
        if ($problem = self::configurationProblem()) {
            throw GoogleSheetsException::configuration($problem);
        }

        $key = json_decode((string) file_get_contents(self::credentialsPath()), true);

        return new self((string) config('services.google_sheets.spreadsheet_id'), [
            'client_email' => $key['client_email'],
            'private_key' => $key['private_key'],
            'token_uri' => $key['token_uri'] ?? 'https://oauth2.googleapis.com/token',
        ]);
    }

    /**
     * Whether both settings are filled in, so the sync should run. A filled-in but broken setup is
     * reported by configurationProblem() when the sync runs.
     */
    public static function isConfigured(): bool
    {
        return filled(config('services.google_sheets.spreadsheet_id')) && filled(config('services.google_sheets.credentials'));
    }

    /**
     * What is wrong with the configuration, or null when it is usable. Never includes anything
     * from inside the key file.
     */
    public static function configurationProblem(): ?string
    {
        $spreadsheetId = (string) config('services.google_sheets.spreadsheet_id');

        if ($spreadsheetId === '') {
            return 'GOOGLE_SHEETS_SPREADSHEET_ID is empty.';
        }

        if (preg_match('/^[A-Za-z0-9_-]{20,}$/', $spreadsheetId) !== 1) {
            return 'GOOGLE_SHEETS_SPREADSHEET_ID does not look like a spreadsheet ID. Use the long ID between /d/ and /edit in the spreadsheet URL.';
        }

        if (blank(config('services.google_sheets.credentials'))) {
            return 'GOOGLE_APPLICATION_CREDENTIALS is empty.';
        }

        $path = self::credentialsPath();
        $shownPath = Str::after($path, base_path().DIRECTORY_SEPARATOR);

        if (str_starts_with(realpath($path) ?: $path, public_path())) {
            return "the key file {$shownPath} is inside public/, where anyone could download it. Move it to secrets/.";
        }

        if (! is_file($path)) {
            return "the key file {$shownPath} (GOOGLE_APPLICATION_CREDENTIALS) does not exist.";
        }

        if (! is_readable($path)) {
            return "the key file {$shownPath} cannot be read.";
        }

        $key = json_decode((string) file_get_contents($path), true);

        if (! is_array($key)) {
            return "the key file {$shownPath} is not valid JSON.";
        }

        if (($key['type'] ?? null) !== 'service_account' || blank($key['client_email'] ?? null) || blank($key['private_key'] ?? null)) {
            return "the key file {$shownPath} is not a service account key (it needs type, client_email and private_key).";
        }

        if (openssl_pkey_get_private($key['private_key']) === false) {
            return "the private key in {$shownPath} cannot be read.";
        }

        return null;
    }

    public static function credentialsPath(): string
    {
        $path = (string) config('services.google_sheets.credentials');

        return preg_match('~^([A-Za-z]:[\\\\/]|/|\\\\)~', $path) === 1 ? $path : base_path($path);
    }

    /**
     * The spreadsheet's tabs by title, with their sheet IDs, grid size and whether a filter is set.
     *
     * @return array<string, array{sheetId: int, rowCount: int, columnCount: int, hasFilter: bool}>
     */
    public function sheets(): array
    {
        $spreadsheet = $this->request('get', '?fields='.rawurlencode('sheets(properties(sheetId,title,gridProperties(rowCount,columnCount)),basicFilter(range))'));

        return collect($spreadsheet['sheets'] ?? [])->mapWithKeys(fn (array $sheet): array => [
            $sheet['properties']['title'] => [
                'sheetId' => (int) $sheet['properties']['sheetId'],
                'rowCount' => (int) ($sheet['properties']['gridProperties']['rowCount'] ?? 0),
                'columnCount' => (int) ($sheet['properties']['gridProperties']['columnCount'] ?? 0),
                'hasFilter' => isset($sheet['basicFilter']),
            ],
        ])->all();
    }

    /**
     * Structural and formatting changes (spreadsheets.batchUpdate).
     *
     * @param  list<array<string, mixed>>  $requests
     * @return array<string, mixed>
     */
    public function batchUpdate(array $requests): array
    {
        return $this->request('post', ':batchUpdate', ['requests' => $requests]);
    }

    /**
     * The values of a range, for example "'Participants'!A:B": one list per row, ending with the
     * last row that has a value. Empty rows in between are empty lists.
     *
     * @return list<list<string>>
     */
    public function values(string $range): array
    {
        return $this->request('get', '/values/'.rawurlencode($range))['values'] ?? [];
    }

    /**
     * Write several ranges in one request, as given.
     *
     * @param  list<array{range: string, values: list<list<mixed>>}>  $data
     */
    public function updateValues(array $data): void
    {
        if ($data !== []) {
            $this->request('post', '/values:batchUpdate', ['valueInputOption' => 'RAW', 'data' => $data]);
        }
    }

    /**
     * @param  'get'|'post'  $method
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $payload = []): array
    {
        $url = self::API.$this->spreadsheetId.$path;
        $client = Http::withToken($this->accessToken())->acceptJson()->timeout(self::TIMEOUT_SECONDS);

        try {
            $response = $method === 'get' ? $client->get($url) : $client->post($url, $payload);
        } catch (ConnectionException) {
            throw new GoogleSheetsException('Could not reach Google Sheets.');
        }

        if ($response->status() === 401) {
            Cache::forget($this->tokenCacheKey());
        }

        if ($response->failed()) {
            throw new GoogleSheetsException("Google Sheets answered HTTP {$response->status()}: {$this->errorMessage($response)}", $response->status());
        }

        return $response->json() ?? [];
    }

    private function accessToken(): string
    {
        return Cache::get($this->tokenCacheKey()) ?? $this->requestAccessToken();
    }

    private function requestAccessToken(): string
    {
        $now = time();
        $assertion = $this->signedJwt([
            'iss' => $this->credentials['client_email'],
            'scope' => self::SCOPE,
            'aud' => $this->credentials['token_uri'],
            'iat' => $now,
            'exp' => $now + 3600,
        ]);

        try {
            $response = Http::asForm()->acceptJson()->timeout(self::TIMEOUT_SECONDS)->post($this->credentials['token_uri'], [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]);
        } catch (ConnectionException) {
            throw new GoogleSheetsException('Could not reach Google to sign in the service account.');
        }

        $token = $response->json('access_token');

        if ($response->failed() || ! is_string($token)) {
            throw new GoogleSheetsException("Google refused the service account sign-in (HTTP {$response->status()}): {$this->errorMessage($response)}", $response->status());
        }

        Cache::put($this->tokenCacheKey(), $token, max(60, (int) $response->json('expires_in', 3600) - 300));

        return $token;
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    private function signedJwt(array $claims): string
    {
        $encode = fn (array $part): string => rtrim(strtr(base64_encode((string) json_encode($part)), '+/', '-_'), '=');
        $unsigned = $encode(['alg' => 'RS256', 'typ' => 'JWT']).'.'.$encode($claims);

        if (! openssl_sign($unsigned, $signature, $this->credentials['private_key'], OPENSSL_ALGO_SHA256)) {
            throw GoogleSheetsException::configuration('the service account private key could not sign in.');
        }

        return $unsigned.'.'.rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    }

    private function tokenCacheKey(): string
    {
        return 'google-sheets:token:'.sha1($this->credentials['client_email']);
    }

    /**
     * Google's short error message, without anything that was sent.
     */
    private function errorMessage(Response $response): string
    {
        $error = $response->json('error');

        $message = match (true) {
            is_array($error) => $error['message'] ?? $error['status'] ?? null,
            is_string($error) => trim($error.' '.($response->json('error_description') ?? '')),
            default => null,
        };

        return Str::limit((string) ($message ?? $response->reason()), 200);
    }
}
