<?php

namespace App\Support;

use App\Enums\FeePeriod;
use App\Enums\IdentityDocument;
use App\Enums\RegistrationType;
use App\Enums\TransactionStatus;
use App\Exceptions\GoogleSheetsException;
use App\Models\Registration;
use App\Models\RegistrationMember;
use Carbon\CarbonInterface;

/**
 * The committee's "Participants" tab in Google Sheets: one row per participant, written from the
 * database, which stays the single source of truth. The sheet is only an administrative view.
 *
 * Rows are found by REGISTRATION ID, never by row number: "UCCR-{BIB}" once the payment is
 * confirmed and BIBs are issued, and "{registration code}-{position}" before that. When a
 * participant's ID changes (their BIB was issued, also after a payment retry) they keep their row
 * through the shared REGISTRATION CODE, which never changes, and a deleted row is added again on
 * the next sync. Syncing the same registration again never adds a duplicate. A bundle's amount appears on
 * its first participant only, so the committee can add up the AMOUNT column.
 */
final class ParticipantsSheet
{
    public const TITLE = 'Participants';

    /**
     * The columns, in this exact order.
     *
     * @var list<string>
     */
    public const HEADERS = [
        'REGISTRATION ID', 'REGISTRATION CODE', 'PAYER NAME', 'FULL NAME', 'EMAIL', 'PHONE', 'DOB', 'GENDER',
        'FOTO KTP', 'RACE CATEGORY', 'REGISTRATION TYPE', 'PRICE TYPE', 'T-SHIRT SIZE', 'EMERGENCY CONTACT NAME',
        'EMERGENCY CONTACT PHONE', 'RIWAYAT PENYAKIT', 'ALERGI OBAT', 'PROVINCE', 'CITY', 'FULL ADDRESS', 'AMOUNT',
        'PAYMENT STATUS', 'REGISTRATION DATE', 'PAYMENT DATE', 'PAYMENT TRANSACTION ID', 'BIB', 'BIB QR',
        'QR SCAN STATUS', 'QR SCANNED AT', 'QR SCANNED BY',
    ];

    /**
     * What a sync needs loaded on each registration.
     *
     * @var list<string>
     */
    public const RELATIONS = ['members', 'ticketPrice', 'payments', 'latestPayment', 'bibCollector:id,name'];

    private const LAST_COLUMN = 'AD';

    /**
     * The tab is grown to at least this many rows, so the column formats cover every participant.
     */
    private const MIN_ROWS = 5000;

    private const TEXT_COLUMNS = [0, 1, 5, 14, 24, 25, 26];

    private const DATE_COLUMNS = [6];

    private const DATE_TIME_COLUMNS = [22, 23, 28];

    private const AMOUNT_COLUMN = 20;

    /**
     * Column widths in pixels, in column order.
     */
    private const WIDTHS = [
        150, 150, 180, 180, 220, 130, 110, 110, 320, 120, 150, 110, 110, 190, 170,
        220, 200, 150, 170, 280, 120, 140, 150, 150, 290, 110, 300, 130, 150, 170,
    ];

    public function __construct(private readonly GoogleSheets $sheets) {}

    /**
     * Prepare the tab: create it, add the header row and set the formatting when missing. Existing
     * participant rows are never cleared or written over, so it is safe to run again.
     *
     * @return list<string> what was done
     */
    public function initialize(): array
    {
        $done = [];
        $sheets = $this->sheets->sheets();

        if (! isset($sheets[self::TITLE])) {
            $this->sheets->batchUpdate([['addSheet' => ['properties' => [
                'title' => self::TITLE,
                'gridProperties' => ['rowCount' => self::MIN_ROWS, 'columnCount' => count(self::HEADERS), 'frozenRowCount' => 1],
            ]]]]);
            $sheets = $this->sheets->sheets();
            $done[] = 'Created the Participants tab.';
        }

        $sheet = $sheets[self::TITLE];

        if ($sheet['columnCount'] < count(self::HEADERS) || $sheet['rowCount'] < self::MIN_ROWS) {
            $this->sheets->batchUpdate([['updateSheetProperties' => [
                'properties' => ['sheetId' => $sheet['sheetId'], 'gridProperties' => [
                    'columnCount' => max($sheet['columnCount'], count(self::HEADERS)),
                    'rowCount' => max($sheet['rowCount'], self::MIN_ROWS),
                ]],
                'fields' => 'gridProperties.columnCount,gridProperties.rowCount',
            ]]]);
            $done[] = 'Made room for all 30 columns.';
        }

        $done[] = $this->writeHeader($sheet['sheetId']);
        $this->sheets->batchUpdate($this->formatting($sheet['sheetId'], addFilter: ! $sheet['hasFilter']));
        $done[] = 'Applied the bold frozen header, filter, column widths and date, text and amount formats.';

        return $done;
    }

    /**
     * What the tab looks like now, without changing anything.
     *
     * @return array{tab: bool, header: bool, participantRows: int}
     */
    public function status(): array
    {
        if (! isset($this->sheets->sheets()[self::TITLE])) {
            return ['tab' => false, 'header' => false, 'participantRows' => 0];
        }

        $identifiers = $this->sheets->values($this->range('A:B'));

        return [
            'tab' => true,
            'header' => ($this->sheets->values($this->range('A1:'.self::LAST_COLUMN.'1'))[0] ?? []) === self::HEADERS,
            'participantRows' => collect($identifiers)->skip(1)->filter(fn (array $row): bool => filled($row[0] ?? null))->count(),
        ];
    }

    /**
     * Write every participant of these registrations: update their rows, add missing ones.
     * Uses one read and one write per kind of change, however many registrations are passed.
     *
     * @param  iterable<Registration>  $registrations  loaded with RELATIONS
     * @return array{updated: int, added: int}
     */
    public function sync(iterable $registrations): array
    {
        $identifiers = $this->readIdentifiers();
        $rowById = [];
        $rowsByCode = [];

        foreach ($identifiers as $index => $row) {
            $id = trim((string) ($row[0] ?? ''));
            $code = trim((string) ($row[1] ?? ''));

            if ($index === 0 || ($id === '' && $code === '')) {
                continue;
            }

            if ($id !== '') {
                $rowById[$id] ??= $index + 1;
            }

            if ($code !== '') {
                $rowsByCode[$code][] = $index + 1;
            }
        }

        $updates = [];
        $additions = [];
        $taken = [];

        foreach ($registrations as $registration) {
            $participants = $this->participantRows($registration);
            $ids = array_column($participants, 'id');

            // Rows of this registration whose ID is no longer one of its participants' IDs.
            $spareRows = array_values(array_filter(
                $rowsByCode[$registration->code] ?? [],
                fn (int $row): bool => ! in_array(trim((string) ($identifiers[$row - 1][0] ?? '')), $ids, true),
            ));

            foreach ($participants as $participant) {
                $row = $rowById[$participant['id']] ?? null;

                if ($row === null || isset($taken[$row])) {
                    $row = null;

                    while ($spareRows !== [] && $row === null) {
                        $candidate = array_shift($spareRows);
                        $row = isset($taken[$candidate]) ? null : $candidate;
                    }
                }

                if ($row === null) {
                    $additions[] = $participant['values'];

                    continue;
                }

                $taken[$row] = true;
                $updates[] = ['range' => $this->range("A{$row}:".self::LAST_COLUMN.$row), 'values' => [$participant['values']]];
            }
        }

        $this->sheets->updateValues($updates);

        if ($additions !== []) {
            $this->sheets->batchUpdate([$this->appendRequest($this->sheetId(), $additions)]);
        }

        return ['updated' => count($updates), 'added' => count($additions)];
    }

    /**
     * One row per participant, the payer first and then bundle members by position.
     *
     * @return list<array{id: string, values: list<string|int|float>}>
     */
    public function participantRows(Registration $registration): array
    {
        $successfulPayment = $registration->payments->firstWhere('status', TransactionStatus::Success);
        $paymentForTransactionId = $registration->isPaid() ? ($successfulPayment ?? $registration->latestPayment) : $registration->latestPayment;
        $identityPhoto = $registration->hasIdentityPhoto() ? route('admin.registrations.identity-photo', $registration) : '';
        $registrationType = $registration->type === RegistrationType::Bundling
            ? ($registration->ticketPrice?->kindLabel() ?? 'Bundle')
            : $registration->type->label();

        $shared = fn (int $position): array => [
            'code' => $registration->code,
            'payer' => $registration->full_name,
            'race' => $registration->category->label(),
            'type' => $registrationType,
            'price' => match ($registration->fee_period) {
                FeePeriod::EarlyBird => 'Early Bird',
                FeePeriod::Regular => 'Normal',
                default => '',
            },
            'amount' => $position === 1 ? $registration->amount : '',
            'payment_status' => self::paymentStatus($registration),
            'registered_at' => self::serial($registration->created_at),
            'paid_at' => $registration->isPaid() ? self::serial($successfulPayment?->paid_at ?? $registration->paid_at) : '',
            'transaction_id' => $paymentForTransactionId?->gateway_transaction_id ?? '',
            'qr' => RacepackQr::payloadFor($registration) ?? '',
            'scan_status' => $registration->hasCollectedBib() ? 'Scanned' : 'Not Scanned',
            'scanned_at' => self::serial($registration->bib_collected_at),
            'scanned_by' => $registration->hasCollectedBib() ? ($registration->bibCollector?->name ?? '') : '',
        ];

        $rows = [self::row($shared(1), [
            'id' => self::participantId($registration, $registration->bib_number, 1),
            'name' => $registration->full_name,
            'email' => $registration->email,
            'phone' => $registration->phone,
            'birth_date' => $registration->birth_date,
            'gender' => $registration->gender->label(),
            'identity_photo' => $identityPhoto,
            'tshirt_size' => $registration->tshirt_size->value,
            'medical_history' => $registration->medical_history,
            'drug_allergies' => $registration->drug_allergies,
            'bib' => $registration->bib_number,
        ], $registration)];

        foreach ($registration->members as $member) {
            /** @var RegistrationMember $member */
            $rows[] = self::row($shared($member->position), [
                'id' => self::participantId($registration, $member->bib_number, $member->position),
                'name' => $member->full_name,
                'email' => '',
                'phone' => $member->phone,
                'birth_date' => $member->birth_date,
                'gender' => $member->gender->label(),
                // One Kartu Keluarga covers the whole family; Couple Bundle members have no photo.
                'identity_photo' => $registration->identityDocument() === IdentityDocument::FamilyCard ? $identityPhoto : '',
                'tshirt_size' => $member->tshirt_size->value,
                'medical_history' => $member->medical_history,
                'drug_allergies' => $member->drug_allergies,
                'bib' => $member->bib_number,
            ], $registration);
        }

        return $rows;
    }

    /**
     * Pending, Settlement, Expired, Cancelled or Failed, from the payment states the backend has
     * verified (see Registration::paymentStage). A Settlement is final; leaving the payment page
     * changes nothing, and a new attempt after a retry shows as Pending again.
     */
    public static function paymentStatus(Registration $registration): string
    {
        return $registration->paymentStage();
    }

    /**
     * A participant's row key: "UCCR-{BIB}" once their BIB is issued, and the registration code
     * with their position before that, for example "UCCR-052-0001-1".
     */
    public static function participantId(Registration $registration, ?string $bibNumber, int $position): string
    {
        return $bibNumber !== null ? BibNumber::participantId($bibNumber) : $registration->code.'-'.$position;
    }

    /**
     * @param  array<string, mixed>  $shared
     * @param  array<string, mixed>  $person
     * @return array{id: string, values: list<string|int|float>}
     */
    private static function row(array $shared, array $person, Registration $registration): array
    {
        $values = [
            $person['id'], $shared['code'], $shared['payer'], $person['name'], $person['email'], $person['phone'],
            self::serial($person['birth_date'], withTime: false), $person['gender'], $person['identity_photo'],
            $shared['race'], $shared['type'], $shared['price'], $person['tshirt_size'],
            $registration->emergency_contact_name, $registration->emergency_contact_phone,
            $person['medical_history'], $person['drug_allergies'],
            $registration->province, $registration->city, $registration->address,
            $shared['amount'], $shared['payment_status'], $shared['registered_at'], $shared['paid_at'],
            $shared['transaction_id'], $person['bib'], $shared['qr'],
            $shared['scan_status'], $shared['scanned_at'], $shared['scanned_by'],
        ];

        // Google skips null cells instead of clearing them, so empty values are written as "".
        return ['id' => $person['id'], 'values' => array_map(fn (mixed $value): string|int|float => $value ?? '', $values)];
    }

    /**
     * A spreadsheet date: days since 30 December 1899 in Jakarta time, so the column's date format
     * shows it (a time is the fraction of the day).
     */
    private static function serial(?CarbonInterface $date, bool $withTime = true): float|int|string
    {
        if ($date === null) {
            return '';
        }

        $local = $date->copy()->setTimezone('Asia/Jakarta');
        $days = ($local->getTimestamp() + $local->getOffset()) / 86400 + 25569;

        return $withTime ? round($days, 8) : (int) floor($days);
    }

    /**
     * The identifier columns, preparing the tab first when it or its header is missing.
     *
     * @return list<list<string>>
     */
    private function readIdentifiers(): array
    {
        try {
            $rows = $this->sheets->values($this->range('A:B'));
        } catch (GoogleSheetsException $exception) {
            // Google answers 400 when the tab does not exist.
            if ($exception->status !== 400) {
                throw $exception;
            }

            $this->initialize();

            return $this->sheets->values($this->range('A:B'));
        }

        if (($rows[0][0] ?? null) !== self::HEADERS[0]) {
            $this->initialize();
            $rows = $this->sheets->values($this->range('A:B'));
        }

        return $rows;
    }

    private function writeHeader(int $sheetId): string
    {
        $headerRange = $this->range('A1:'.self::LAST_COLUMN.'1');
        $current = $this->sheets->values($headerRange)[0] ?? [];

        if ($current === self::HEADERS) {
            return 'The header row is already in place.';
        }

        $isParticipantRow = $current !== [] && ($current[0] ?? null) !== self::HEADERS[0];

        if ($isParticipantRow) {
            // Row 1 holds data: move everything down one row instead of writing over it.
            $this->sheets->batchUpdate([['insertDimension' => [
                'range' => ['sheetId' => $sheetId, 'dimension' => 'ROWS', 'startIndex' => 0, 'endIndex' => 1],
                'inheritFromBefore' => false,
            ]]]);
        }

        $this->sheets->updateValues([['range' => $headerRange, 'values' => [self::HEADERS]]]);

        return $isParticipantRow ? 'Moved the existing rows down one and added the header row.' : 'Wrote the header row.';
    }

    private function sheetId(): int
    {
        return $this->sheets->sheets()[self::TITLE]['sheetId']
            ?? throw new GoogleSheetsException('The Participants tab disappeared while writing. Run "php artisan sheets:initialize".');
    }

    /**
     * Add rows below the last row with data. Every cell gets its value and the data formatting in
     * the same request, so new rows never take on the header's formatting (rows inserted under the
     * header otherwise copy it) and existing rows are never written over. Text is sent as text, so
     * a value starting with "=" is never run as a formula.
     *
     * @param  list<list<mixed>>  $rows
     * @return array<string, mixed>
     */
    private function appendRequest(int $sheetId, array $rows): array
    {
        $cell = fn (mixed $value, int $column): object => (object) array_filter([
            'userEnteredValue' => match (true) {
                $value === '' || $value === null => null,
                is_int($value) || is_float($value) => ['numberValue' => $value],
                default => ['stringValue' => (string) $value],
            },
            'userEnteredFormat' => ($format = self::numberFormat($column)) === null ? null : ['numberFormat' => $format],
        ], fn (?array $part): bool => $part !== null);

        return ['appendCells' => [
            'sheetId' => $sheetId,
            'rows' => array_map(fn (array $values): array => ['values' => array_map($cell, $values, array_keys($values))], $rows),
            'fields' => 'userEnteredValue,userEnteredFormat',
        ]];
    }

    /**
     * The number format of a participant column, or null where Google's automatic format is kept.
     *
     * @return array{type: string, pattern: string}|null
     */
    private static function numberFormat(int $column): ?array
    {
        return match (true) {
            in_array($column, self::TEXT_COLUMNS, true) => ['type' => 'TEXT', 'pattern' => '@'],
            in_array($column, self::DATE_COLUMNS, true) => ['type' => 'DATE', 'pattern' => 'yyyy-mm-dd'],
            in_array($column, self::DATE_TIME_COLUMNS, true) => ['type' => 'DATE_TIME', 'pattern' => 'yyyy-mm-dd hh:mm'],
            $column === self::AMOUNT_COLUMN => ['type' => 'NUMBER', 'pattern' => '"Rp"#,##0'],
            default => null,
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function formatting(int $sheetId, bool $addFilter): array
    {
        $columns = count(self::HEADERS);

        $requests = [
            ['updateSheetProperties' => [
                'properties' => ['sheetId' => $sheetId, 'gridProperties' => ['frozenRowCount' => 1]],
                'fields' => 'gridProperties.frozenRowCount',
            ]],
            ['repeatCell' => [
                'range' => ['sheetId' => $sheetId, 'startRowIndex' => 0, 'endRowIndex' => 1, 'startColumnIndex' => 0, 'endColumnIndex' => $columns],
                'cell' => ['userEnteredFormat' => [
                    'textFormat' => ['bold' => true],
                    'backgroundColor' => ['red' => 0.91, 'green' => 0.89, 'blue' => 0.99],
                    'verticalAlignment' => 'MIDDLE',
                ]],
                'fields' => 'userEnteredFormat(textFormat,backgroundColor,verticalAlignment)',
            ]],
        ];

        if ($addFilter) {
            $requests[] = ['setBasicFilter' => ['filter' => ['range' => ['sheetId' => $sheetId, 'startRowIndex' => 0, 'startColumnIndex' => 0, 'endColumnIndex' => $columns]]]];
        }

        foreach (self::WIDTHS as $column => $width) {
            $requests[] = ['updateDimensionProperties' => [
                'range' => ['sheetId' => $sheetId, 'dimension' => 'COLUMNS', 'startIndex' => $column, 'endIndex' => $column + 1],
                'properties' => ['pixelSize' => $width],
                'fields' => 'pixelSize',
            ]];
        }

        foreach (array_keys(self::HEADERS) as $column) {
            if ($format = self::numberFormat($column)) {
                $requests[] = ['repeatCell' => [
                    'range' => ['sheetId' => $sheetId, 'startRowIndex' => 1, 'startColumnIndex' => $column, 'endColumnIndex' => $column + 1],
                    'cell' => ['userEnteredFormat' => ['numberFormat' => $format]],
                    'fields' => 'userEnteredFormat.numberFormat',
                ]];
            }
        }

        return $requests;
    }

    private function range(string $cells): string
    {
        return "'".self::TITLE."'!".$cells;
    }
}
