<?php

use App\Enums\TransactionStatus;
use App\Models\Admin;
use App\Models\Registration;
use App\Models\RegistrationMember;
use App\Support\GoogleSheets;
use App\Support\ParticipantsSheet;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\Fakes\FakeGoogleSheets;

// These tests sync by hand; the automatic sync after saves is covered in SyncRegistrationToParticipantsSheetTest.
beforeEach(fn () => Queue::fake());

function participantsSheet(): ParticipantsSheet
{
    return new ParticipantsSheet(GoogleSheets::fromConfig());
}

/**
 * @return array{updated: int, added: int}
 */
function syncToSheet(Registration ...$registrations): array
{
    return participantsSheet()->sync(array_map(fn (Registration $registration): Registration => $registration->fresh(ParticipantsSheet::RELATIONS), $registrations));
}

/**
 * A registration (in a factory state, or waiting for payment) whose latest payment attempt has this status.
 */
function registrationWithAttempt(?string $state, TransactionStatus $status): Registration
{
    $registration = ($state === null ? Registration::factory() : Registration::factory()->{$state}())->create();
    $registration->payments()->create(['order_id' => $registration->code.'-1', 'amount' => 100000, 'status' => $status]);

    return $registration;
}

/**
 * A bundle registration ("UCCR-{prefix}-0001"); when paid, its participants have consecutive BIBs from $payerBib.
 *
 * @param  list<string>  $memberNames
 */
function sheetBundle(string $bundle, string $payerBib, array $memberNames, bool $paid = true): Registration
{
    $factory = Registration::factory()->bundle($bundle);
    $factory = $bundle === 'family' ? $factory->withFamilyCardPhoto() : $factory->withKtpPhoto();
    $registration = ($paid ? $factory->paid() : $factory)->create([
        'code' => 'UCCR-'.substr($payerBib, 0, 3).'-0001', 'bib_number' => $paid ? $payerBib : null,
        'full_name' => 'Hendra Wijaya', 'email' => 'hendra@example.com', 'amount' => 100000,
        'emergency_contact_name' => 'Sari', 'emergency_contact_phone' => '081211112222', 'province' => 'Jawa Timur', 'city' => 'Kota Surabaya', 'address' => 'Jl. Contoh 1',
    ]);

    foreach ($memberNames as $index => $name) {
        $bib = substr($payerBib, 0, 3).str_pad((string) ((int) substr($payerBib, 3) + $index + 1), 4, '0', STR_PAD_LEFT);
        RegistrationMember::factory()->for($registration)->create([
            'position' => $index + 2, 'full_name' => $name, 'bib_number' => $paid ? $bib : null,
            'medical_history' => 'Asma', 'drug_allergies' => 'Tidak Ada',
        ]);
    }

    return $registration;
}

describe('initializing the spreadsheet', function () {
    it('creates the Participants tab with the header row, frozen bold header, filter, widths and formats', function () {
        $google = FakeGoogleSheets::install();

        expect(participantsSheet()->initialize())->toContain('Created the Participants tab.', 'Wrote the header row.');

        $tab = $google->tabs['Participants'];
        $requests = collect($google->batchRequests());
        $formats = $requests->filter(fn (array $request): bool => isset($request['repeatCell']['cell']['userEnteredFormat']['numberFormat']))
            ->mapWithKeys(fn (array $request): array => [$request['repeatCell']['range']['startColumnIndex'] => $request['repeatCell']['cell']['userEnteredFormat']['numberFormat']['type']])
            ->sortKeys()
            ->all();

        expect($tab['rows'])->toBe([ParticipantsSheet::HEADERS])
            ->and($tab)->columnCount->toBe(30)->rowCount->toBeGreaterThanOrEqual(5000)->frozenRowCount->toBe(1)->hasFilter->toBeTrue()
            ->and($requests->firstWhere('repeatCell.cell.userEnteredFormat.textFormat.bold', true))->not->toBeNull()
            ->and($requests->whereNotNull('updateDimensionProperties'))->toHaveCount(30)
            ->and($formats)->toBe([
                0 => 'TEXT', 1 => 'TEXT', 5 => 'TEXT', 6 => 'DATE', 14 => 'TEXT', 20 => 'NUMBER',
                22 => 'DATE_TIME', 23 => 'DATE_TIME', 24 => 'TEXT', 25 => 'TEXT', 26 => 'TEXT', 28 => 'DATE_TIME',
            ]);
    });

    it('keeps existing rows, the header and the filter when run again', function () {
        $google = FakeGoogleSheets::install();
        participantsSheet()->initialize();
        $google->tabs['Participants']['rows'][] = ['UCCR-0320001', 'UCCR-0320001', 'Rina Wijaya'];

        expect(participantsSheet()->initialize())->toContain('The header row is already in place.')->not->toContain('Created the Participants tab.')
            ->and($google->tabs['Participants']['rows'])->toBe([ParticipantsSheet::HEADERS, ['UCCR-0320001', 'UCCR-0320001', 'Rina Wijaya']])
            ->and(collect($google->batchRequests())->whereNotNull('setBasicFilter'))->toHaveCount(1);
    });

    it('adds the header above data in a tab made by hand and makes room for 30 columns', function () {
        $google = FakeGoogleSheets::install();
        $google->addTab('Participants', [['UCCR-0320001', 'UCCR-0320001', 'Rina Wijaya']]);

        expect(participantsSheet()->initialize())->toContain('Made room for all 30 columns.', 'Moved the existing rows down one and added the header row.')
            ->and($google->tabs['Participants']['columnCount'])->toBe(30)
            ->and($google->tabs['Participants']['rows'][0])->toBe(ParticipantsSheet::HEADERS)
            ->and($google->tabs['Participants']['rows'][1])->toBe(['UCCR-0320001', 'UCCR-0320001', 'Rina Wijaya']);
    });

    it('rewrites an outdated header row in place without moving data', function () {
        $google = FakeGoogleSheets::install();
        $google->addTab('Participants', [['REGISTRATION ID', 'CODE'], ['UCCR-0320001', 'UCCR-0320001']]);

        participantsSheet()->initialize();

        expect($google->tabs['Participants']['rows'])->toHaveCount(2)
            ->and($google->tabs['Participants']['rows'][0])->toBe(ParticipantsSheet::HEADERS)
            ->and($google->tabs['Participants']['rows'][1][0])->toBe('UCCR-0320001');
    });

    it('prepares a missing tab during the first sync', function () {
        $registration = Registration::factory()->create(['code' => 'UCCR-032-0001']);
        $google = FakeGoogleSheets::install();

        syncToSheet($registration);

        expect($google->tabs['Participants']['rows'][0])->toBe(ParticipantsSheet::HEADERS)
            ->and($google->participants())->toHaveCount(1);
    });
});

describe('writing participants', function () {
    it('writes an individual registration as one pending row with every column', function () {
        $this->travelTo('2026-12-06 09:30:00');
        $registration = Registration::factory()->withKtpPhoto()->create([
            'type' => 'external', 'category' => '3k', 'fee_period' => 'early_bird', 'code' => 'UCCR-032-0001',
            'full_name' => 'Rina Wijaya', 'email' => 'rina@example.com', 'phone' => '081234567890', 'birth_date' => '2000-05-12', 'gender' => 'female',
            'tshirt_size' => 'M', 'emergency_contact_name' => 'Budi Wijaya', 'emergency_contact_phone' => '081298765432',
            'medical_history' => 'Asma ringan', 'drug_allergies' => 'Penisilin', 'province' => 'Jawa Timur', 'city' => 'Kota Surabaya',
            'address' => 'Jl. Raya CitraLand 1', 'amount' => 100000,
        ]);
        $google = FakeGoogleSheets::install();

        expect(syncToSheet($registration))->toBe(['updated' => 0, 'added' => 1])
            // Before the payment is confirmed there is no BIB, so the ID is the code and the position.
            ->and($google->participant('UCCR-032-0001-1'))->toBe([
                'REGISTRATION ID' => 'UCCR-032-0001-1', 'REGISTRATION CODE' => 'UCCR-032-0001', 'PAYER NAME' => 'Rina Wijaya', 'FULL NAME' => 'Rina Wijaya',
                'EMAIL' => 'rina@example.com', 'PHONE' => '081234567890', 'DOB' => sheetDate('2000-05-12', withTime: false), 'GENDER' => 'Perempuan',
                'FOTO KTP' => route('admin.registrations.identity-photo', $registration), 'RACE CATEGORY' => '3K', 'REGISTRATION TYPE' => 'External',
                'PRICE TYPE' => 'Early Bird', 'T-SHIRT SIZE' => 'M', 'EMERGENCY CONTACT NAME' => 'Budi Wijaya', 'EMERGENCY CONTACT PHONE' => '081298765432',
                'RIWAYAT PENYAKIT' => 'Asma ringan', 'ALERGI OBAT' => 'Penisilin', 'PROVINCE' => 'Jawa Timur', 'CITY' => 'Kota Surabaya',
                'FULL ADDRESS' => 'Jl. Raya CitraLand 1', 'AMOUNT' => 100000, 'PAYMENT STATUS' => 'Pending', 'REGISTRATION DATE' => sheetDate('2026-12-06 09:30:00'),
                'PAYMENT DATE' => '', 'PAYMENT TRANSACTION ID' => '', 'BIB' => '', 'BIB QR' => '', 'QR SCAN STATUS' => 'Not Scanned', 'QR SCANNED AT' => '', 'QR SCANNED BY' => '',
            ]);
    });

    it('writes one row per bundle participant, sharing the code, with the amount on the first row only', function (string $bundle, string $payerBib, array $members, string $type, array $photos) {
        $registration = sheetBundle($bundle, $payerBib, $members, paid: false);
        $google = FakeGoogleSheets::install();

        syncToSheet($registration);
        $rows = collect($google->participants());

        expect($rows)->toHaveCount(count($members) + 1)
            ->and($rows->pluck('FULL NAME')->all())->toBe(['Hendra Wijaya', ...$members])
            ->and($rows->pluck('REGISTRATION CODE')->unique()->all())->toBe([$registration->code])
            ->and($rows->pluck('REGISTRATION ID')->all())->toBe(array_map(fn (int $position): string => "{$registration->code}-{$position}", range(1, count($members) + 1)))
            ->and($rows->pluck('REGISTRATION ID')->unique())->toHaveCount(count($members) + 1)
            ->and($rows->pluck('PAYER NAME')->unique()->all())->toBe(['Hendra Wijaya'])
            ->and($rows->pluck('AMOUNT')->all())->toBe([100000, ...array_fill(0, count($members), '')])
            ->and($rows->pluck('PAYMENT STATUS')->unique()->all())->toBe(['Pending'])
            ->and($rows->pluck('REGISTRATION TYPE')->unique()->all())->toBe([$type])
            ->and($rows->pluck('PRICE TYPE')->unique()->all())->toBe([''])
            ->and($rows->pluck('EMAIL')->all())->toBe(['hendra@example.com', ...array_fill(0, count($members), '')])
            ->and($rows->pluck('FULL ADDRESS')->unique()->all())->toBe(['Jl. Contoh 1'])
            ->and($rows->pluck('EMERGENCY CONTACT NAME')->unique()->all())->toBe(['Sari'])
            ->and($rows->pluck('RIWAYAT PENYAKIT')->skip(1)->unique()->values()->all())->toBe(['Asma'])
            ->and($rows->pluck('FOTO KTP')->map(fn (string $link): bool => $link === route('admin.registrations.identity-photo', $registration))->all())->toBe($photos);
    })->with([
        'Couple Bundle' => ['couple', '0550001', ['Gita Permata'], 'Couple Bundle', [true, false]],
        'Family Bundle' => ['family', '0350001', ['Indah Wijaya', 'Joko Wijaya', 'Kartika Wijaya'], 'Family Bundle', [true, true, true, true]],
    ]);

    it('updates rows by registration ID and never adds a duplicate', function () {
        $registration = sheetBundle('family', '0350001', ['Indah Wijaya', 'Joko Wijaya', 'Kartika Wijaya'], paid: false);
        $google = FakeGoogleSheets::install();

        expect(syncToSheet($registration))->toBe(['updated' => 0, 'added' => 4])
            ->and(syncToSheet($registration))->toBe(['updated' => 4, 'added' => 0])
            ->and(syncToSheet($registration))->toBe(['updated' => 4, 'added' => 0])
            ->and($google->participants())->toHaveCount(4);
    });

    it('adds back a row the committee deleted, without touching the others', function () {
        $first = Registration::factory()->create(['code' => 'UCCR-032-0001']);
        $second = Registration::factory()->create(['code' => 'UCCR-032-0002']);
        $google = FakeGoogleSheets::install();
        syncToSheet($first, $second);

        $google->tabs['Participants']['rows'][1] = [];

        expect(syncToSheet($first, $second))->toBe(['updated' => 1, 'added' => 1])
            ->and(collect($google->participants())->pluck('REGISTRATION ID')->sort()->values()->all())->toBe(['UCCR-032-0001-1', 'UCCR-032-0002-1']);
    });

    it('keeps the rows of a registration whose IDs changed when its BIBs were issued, through the shared registration code', function () {
        $registration = sheetBundle('couple', '0550001', ['Gita Permata'], paid: false);
        $google = FakeGoogleSheets::install();
        syncToSheet($registration);

        expect(collect($google->participants())->pluck('REGISTRATION ID')->all())->toBe(['UCCR-055-0001-1', 'UCCR-055-0001-2']);

        $registration->markAsPaid();

        expect(syncToSheet($registration->fresh()))->toBe(['updated' => 2, 'added' => 0])
            ->and($google->participants())->toHaveCount(2)
            ->and(collect($google->participants())->map(fn (array $row): array => Arr::only($row, ['REGISTRATION ID', 'REGISTRATION CODE', 'FULL NAME', 'PAYMENT STATUS', 'BIB']))->all())->toBe([
                ['REGISTRATION ID' => 'UCCR-0550001', 'REGISTRATION CODE' => 'UCCR-055-0001', 'FULL NAME' => 'Hendra Wijaya', 'PAYMENT STATUS' => 'Settlement', 'BIB' => '0550001'],
                ['REGISTRATION ID' => 'UCCR-0550002', 'REGISTRATION CODE' => 'UCCR-055-0001', 'FULL NAME' => 'Gita Permata', 'PAYMENT STATUS' => 'Settlement', 'BIB' => '0550002'],
            ]);
    });

    it('writes participant text exactly as typed, never as a formula', function () {
        $registration = Registration::factory()->create(['code' => 'UCCR-032-0001', 'full_name' => '=HYPERLINK("https://example.com","klik")']);
        $google = FakeGoogleSheets::install();

        syncToSheet($registration);

        $valueWrites = collect($google->writes())->filter(fn (array $call): bool => str_contains($call['path'], '/values'));
        $appendedName = collect($google->batchRequests())->whereNotNull('appendCells')->sole()['appendCells']['rows'][0]['values'][3];

        expect($google->participant('UCCR-032-0001-1')['FULL NAME'])->toBe('=HYPERLINK("https://example.com","klik")')
            ->and($appendedName['userEnteredValue'])->toBe(['stringValue' => '=HYPERLINK("https://example.com","klik")'])
            ->and($valueWrites)->not->toBeEmpty()
            ->and($valueWrites->map(fn (array $call): ?string => $call['query']['valueInputOption'] ?? $call['body']['valueInputOption'] ?? null)->unique()->values()->all())->toBe(['RAW']);
    });

    it('adds new rows below the data with the date, amount and text formats, never the header formatting', function () {
        $registration = sheetBundle('couple', '0550001', ['Gita Permata'], paid: false);
        $google = FakeGoogleSheets::install();

        syncToSheet($registration);

        $append = collect($google->batchRequests())->whereNotNull('appendCells')->sole()['appendCells'];
        $payer = $append['rows'][0]['values'];
        $dateTime = ['numberFormat' => ['type' => 'DATE_TIME', 'pattern' => 'yyyy-mm-dd hh:mm']];

        expect($append)->sheetId->toBe($google->tabs['Participants']['sheetId'])->fields->toBe('userEnteredValue,userEnteredFormat')
            ->and($append['rows'])->toHaveCount(2)
            ->and($payer)->toHaveCount(30)
            ->and($payer[0])->toBe(['userEnteredValue' => ['stringValue' => 'UCCR-055-0001-1'], 'userEnteredFormat' => ['numberFormat' => ['type' => 'TEXT', 'pattern' => '@']]])
            ->and($payer[2])->toBe(['userEnteredValue' => ['stringValue' => 'Hendra Wijaya']])
            ->and($payer[6]['userEnteredFormat'])->toBe(['numberFormat' => ['type' => 'DATE', 'pattern' => 'yyyy-mm-dd']])
            ->and($payer[20])->toBe(['userEnteredValue' => ['numberValue' => 100000], 'userEnteredFormat' => ['numberFormat' => ['type' => 'NUMBER', 'pattern' => '"Rp"#,##0']]])
            ->and($payer[22]['userEnteredFormat'])->toBe($dateTime)
            ->and($payer[23])->toBe(['userEnteredFormat' => $dateTime])
            ->and($payer[28])->toBe(['userEnteredFormat' => $dateTime])
            ->and(collect($payer)->pluck('userEnteredFormat.textFormat')->filter()->all())->toBe([])
            ->and($google->tabs['Participants']['rows'])->toHaveCount(3);
    });
});

describe('payment and scan columns', function () {
    it('shows the payment status the backend verified', function (Closure $makeRegistration, string $status) {
        $registration = $makeRegistration();
        $google = FakeGoogleSheets::install();

        syncToSheet($registration);

        expect(collect($google->participants())->pluck('PAYMENT STATUS')->all())->toBe([$status]);
    })->with([
        'waiting for payment' => [fn () => Registration::factory()->create(), 'Pending'],
        'paid' => [fn () => Registration::factory()->paid()->create(), 'Settlement'],
        'payment window over' => [fn () => Registration::factory()->expired()->create(), 'Expired'],
        'expired at Midtrans' => [fn () => registrationWithAttempt('failed', TransactionStatus::Expired), 'Expired'],
        'cancelled at Midtrans' => [fn () => registrationWithAttempt('failed', TransactionStatus::Cancelled), 'Cancelled'],
        'failed at Midtrans' => [fn () => registrationWithAttempt('failed', TransactionStatus::Failed), 'Failed'],
        'no QRIS could be made' => [fn () => Registration::factory()->failed()->create(), 'Failed'],
        'a new attempt after an expired one' => [fn () => registrationWithAttempt(null, TransactionStatus::Expired), 'Pending'],
    ]);

    it('fills in the verified payment date, Midtrans transaction ID, BIBs and racepack QR once paid', function () {
        $registration = sheetBundle('couple', '0550001', ['Gita Permata']);
        $registration->payments()->create([
            'order_id' => 'UCCR-0550001-1', 'gateway_transaction_id' => '9b1f2c3d-midtrans', 'amount' => 100000,
            'status' => TransactionStatus::Success, 'paid_at' => Carbon::parse('2026-12-07 10:15:00', 'Asia/Jakarta'),
        ]);
        $google = FakeGoogleSheets::install();

        syncToSheet($registration);
        $rows = collect($google->participants());

        expect($rows->pluck('PAYMENT STATUS')->unique()->all())->toBe(['Settlement'])
            ->and($rows->pluck('PAYMENT DATE')->unique()->all())->toBe([sheetDate('2026-12-07 10:15:00')])
            ->and($rows->pluck('PAYMENT TRANSACTION ID')->unique()->all())->toBe(['9b1f2c3d-midtrans'])
            ->and($rows->pluck('BIB')->all())->toBe(['0550001', '0550002'])
            ->and($rows->pluck('REGISTRATION ID')->all())->toBe(['UCCR-0550001', 'UCCR-0550002'])
            ->and($rows->pluck('BIB QR')->unique()->all())->toBe(['UCCR-0550001;UCCR-0550002']);
    });

    it('marks every participant of a scanned registration as scanned, with when and by whom', function () {
        $admin = Admin::factory()->create(['name' => 'Panitia Racepack']);
        $registration = sheetBundle('family', '0350001', ['Indah Wijaya', 'Joko Wijaya', 'Kartika Wijaya']);
        $registration->forceFill(['bib_collected_at' => Carbon::parse('2027-04-08 08:15:00'), 'bib_collected_by' => $admin->id])->save();
        $google = FakeGoogleSheets::install();

        syncToSheet($registration);
        $rows = collect($google->participants());

        expect($rows->pluck('QR SCAN STATUS')->all())->toBe(array_fill(0, 4, 'Scanned'))
            ->and($rows->pluck('QR SCANNED AT')->unique()->all())->toBe([sheetDate('2027-04-08 08:15:00')])
            ->and($rows->pluck('QR SCANNED BY')->unique()->all())->toBe(['Panitia Racepack']);
    });
});
