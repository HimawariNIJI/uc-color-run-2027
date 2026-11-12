<?php

use App\Enums\QrScanRejection;
use App\Enums\QrScanSource;
use App\Enums\QrScanStatus;
use App\Models\Admin;
use App\Models\QrScan;
use App\Models\Registration;
use App\Models\RegistrationMember;
use App\Models\User;

beforeEach(function () {
    $this->admin = Admin::factory()->create(['name' => 'Panitia Racepack']);
});

/**
 * A paid 3K Early Bird External registration with BIB 0320001.
 *
 * @param  array<string, mixed>  $attributes
 */
function paidRunner(array $attributes = []): Registration
{
    return Registration::factory()->paid()->create([
        'type' => 'external', 'category' => '3k', 'fee_period' => 'early_bird',
        'code' => 'UCCR-0320001', 'bib_number' => '0320001',
        'full_name' => 'Rina Wijaya', 'email' => 'rina@example.com', 'phone' => '081234567890',
        ...$attributes,
    ]);
}

/**
 * A paid bundle: the payer has $payerBib and the members the following numbers, in order.
 *
 * @param  list<string>  $memberNames
 */
function paidBundle(string $bundle, string $payerBib, array $memberNames): Registration
{
    $registration = Registration::factory()->bundle($bundle)->paid()->create([
        'code' => 'UCCR-'.$payerBib, 'bib_number' => $payerBib, 'full_name' => 'Hendra Wijaya',
    ]);

    foreach ($memberNames as $index => $name) {
        $bib = substr($payerBib, 0, 3).str_pad((string) ((int) substr($payerBib, 3) + $index + 1), 4, '0', STR_PAD_LEFT);
        RegistrationMember::factory()->for($registration)->create(['position' => $index + 2, 'full_name' => $name, 'bib_number' => $bib]);
    }

    return $registration->fresh('members');
}

describe('the scanner page', function () {
    it('lists the ten most recent accepted scans, newest first, without refused scans', function () {
        QrScan::factory()->for(paidRunner(), 'registration')->create(['scanned_at' => now()]);
        QrScan::factory()->count(9)->create(['scanned_at' => now()->subHour()]);
        $oldest = QrScan::factory()->create(['scanned_at' => now()->subDay()]);
        QrScan::factory()->rejected(QrScanRejection::NotFound)->create(['payload' => 'UCCR-0329999', 'scanned_at' => now()->addMinute()]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.scanner.index'))
            ->assertOk()
            ->assertSee(['QR Scanner', 'Scan the QR Code to collect your BIB', 'Start Camera', 'Recent QR Scanned'])
            ->assertSeeInOrder(['Recent QR Scanned', 'UCCR-0320001', 'Rina Wijaya', '0320001'])
            ->assertDontSee([$oldest->registration->code, 'UCCR-0329999']);
    });

    it('shows a bundle scan as one row with every participant', function () {
        QrScan::factory()->for(paidBundle('family', '0350001', ['Indah Wijaya', 'Joko Wijaya', 'Kartika Wijaya']), 'registration')->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.scanner.index'))
            ->assertSeeInOrder(['UCCR-0350001', 'Hendra Wijaya', '4 peserta', '3K Family Bundle', '0350001', '0350002', '0350003', '0350004']);

        expect(substr_count($response->getContent(), 'data-scanner-row'))->toBe(1);
    });
});

describe('scanning a valid racepack QR', function () {
    it('accepts an individual QR, shows its details, hands over the BIB and records one scan', function () {
        $this->freezeSecond();
        $registration = paidRunner(['medical_history' => 'Asma ringan', 'drug_allergies' => 'Penisilin', 'amount' => 100000]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.scanner.store'), ['payload' => 'UCCR-0320001'])
            ->assertOk()
            ->assertJson(['tone' => 'success', 'message' => 'Successfully scanned UCCR-0320001. Your BIB Number is 0320001.']);

        expect($response->json('modal'))
            ->toContain('QR Racepack Valid', 'UCCR-0320001 · 1 peserta', 'Serahkan BIB', 'BIB 0320001 diserahkan saat scan ini.')
            ->toContain('3K Early Bid', 'External', 'Early Bid', 'Paid', 'Rp100.000', $registration->paid_at->translatedFormat('j M Y, H:i'))
            ->toContain('Full Nama (Nama Pembayar)', 'Rina Wijaya', 'rina@example.com', '081234567890')
            ->toContain('Peserta 1 · Rina Wijaya', 'Asma ringan', 'Penisilin')
            ->and(substr_count($response->json('modal'), 'data-scan-participant'))->toBe(1)
            ->and($response->json('row'))->toContain('UCCR-0320001', 'Rina Wijaya', '0320001');

        expect(QrScan::sole())
            ->registration_id->toBe($registration->id)
            ->admin_id->toBe($this->admin->id)
            ->status->toBe(QrScanStatus::Accepted)
            ->source->toBe(QrScanSource::Camera)
            ->payload->toBe('UCCR-0320001')
            ->bib_handed_over->toBeTrue()
            ->scanned_at->toEqual(now())
            ->and($registration->fresh())
            ->bib_collected_at->toEqual(now())
            ->bib_collected_by->toBe($this->admin->id);
    });

    it('accepts a bundle QR as one scan showing every participant with their own BIB', function (string $bundle, string $payerBib, array $members, string $payload, string $message) {
        $registration = paidBundle($bundle, $payerBib, $members);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.scanner.store'), ['payload' => $payload])
            ->assertJson(['tone' => 'success', 'message' => $message]);

        $modal = $response->json('modal');
        $participants = count($members) + 1;
        expect(substr_count($modal, 'data-scan-participant'))->toBe($participants)
            ->and($modal)->toContain("{$registration->code} · {$participants} peserta", 'Peserta 1 · Hendra Wijaya')
            ->and($response->json('row'))->toContain(...explode(';', str_replace('UCCR-', '', $payload)));

        foreach (array_values($members) as $index => $name) {
            expect($modal)->toContain('Peserta '.($index + 2).' · '.$name);
        }

        expect(QrScan::query()->accepted()->sole())
            ->registration_id->toBe($registration->id)
            ->payload->toBe($payload)
            ->and($registration->fresh()->hasCollectedBib())->toBeTrue();
    })->with([
        'Couple Bundle' => ['couple', '0550001', ['Gita Permata'], 'UCCR-0550001;UCCR-0550002', 'Successfully scanned UCCR-0550001. BIB Numbers: 0550001, 0550002 (2 peserta).'],
        'Family Bundle' => ['family', '0350001', ['Indah Wijaya', 'Joko Wijaya', 'Kartika Wijaya'], 'UCCR-0350001;UCCR-0350002;UCCR-0350003;UCCR-0350004', 'Successfully scanned UCCR-0350001. BIB Numbers: 0350001, 0350002, 0350003, 0350004 (4 peserta).'],
    ]);

    it('records repeated camera frames of the same QR once', function () {
        paidRunner();
        $scan = fn () => $this->actingAs($this->admin, 'admin')->postJson(route('admin.scanner.store'), ['payload' => 'UCCR-0320001']);

        $scan()->assertJson(['tone' => 'success']);
        $scan()->assertJson(['tone' => 'warning', 'message' => 'QR UCCR-0320001 baru saja dipindai, jadi tidak dicatat dua kali.', 'row' => null])
            ->assertJsonPath('modal', fn (string $modal): bool => str_contains($modal, 'UCCR-0320001'));

        expect(QrScan::query()->accepted()->count())->toBe(1);

        // Scanned again on purpose later: a new scan, and the desk is told the BIB was already handed over.
        $this->travel(QrScan::DUPLICATE_WINDOW_SECONDS + 1)->seconds();
        $scan()->assertJson(['tone' => 'warning'])->assertJsonPath('row', fn (string $row): bool => str_contains($row, 'UCCR-0320001'));

        expect(QrScan::query()->accepted()->count())->toBe(2)
            ->and(QrScan::query()->accepted()->latest('id')->first()->bib_handed_over)->toBeFalse();
    });

    it('warns when the BIB was already collected, still shows the details and records the scan', function () {
        $firstDesk = Admin::factory()->create(['name' => 'Meja 1']);
        $registration = paidRunner(['bib_collected_at' => '2027-04-08 08:15:00', 'bib_collected_by' => $firstDesk->id]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.scanner.store'), ['payload' => 'UCCR-0320001'])
            ->assertJson(['tone' => 'warning', 'message' => 'UCCR-0320001 sudah mengambil BIB 0320001 pada 8 Apr 2027, 08:15.']);

        expect($response->json('modal'))->toContain('BIB sudah diambil sebelumnya', 'Diambil 8 Apr 2027, 08:15 oleh Meja 1')
            ->and($registration->fresh()->bib_collected_by)->toBe($firstDesk->id)
            ->and(QrScan::query()->accepted()->sole()->bib_handed_over)->toBeFalse();
    });

    it('accepts a typed registration code or one participant ID when no camera is available', function (string $typed) {
        $registration = paidBundle('couple', '0550001', ['Gita Permata']);
        $registration->forceFill(['code' => 'UCR-2027-ABCDE'])->save();

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.scanner.store'), ['code' => $typed])
            ->assertJson(['tone' => 'success']);

        expect(substr_count($response->json('modal'), 'data-scan-participant'))->toBe(2)
            ->and(QrScan::query()->accepted()->sole())->source->toBe(QrScanSource::Manual);
    })->with(['registration code in any letter case' => ' ucr-2027-abcde ', 'a member\'s participant ID' => 'UCCR-0550002']);
});

describe('refusing a racepack QR', function () {
    it('refuses the whole scan and records it only as a refused attempt', function (string $payload, QrScanRejection $reason, string $message) {
        paidRunner();
        paidBundle('couple', '0550001', ['Gita Permata']);
        $family = paidBundle('family', '0350001', ['Indah Wijaya', 'Joko Wijaya', 'Kartika Wijaya']);
        $family->members()->where('position', 4)->delete();
        Registration::factory()->create(['code' => 'UCCR-0330007', 'bib_number' => '0330007', 'type' => 'internal', 'category' => '3k', 'fee_period' => 'normal']);

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.scanner.store'), ['payload' => $payload])
            ->assertOk()
            ->assertExactJson(['tone' => 'error', 'message' => $message, 'modal' => null, 'row' => null]);

        expect(QrScan::query()->accepted()->exists())->toBeFalse()
            ->and(Registration::query()->bibCollected()->exists())->toBeFalse()
            ->and(QrScan::sole())->status->toBe(QrScanStatus::Rejected)->rejection_reason->toBe($reason)->admin_id->toBe($this->admin->id);
    })->with([
        'a QR from elsewhere' => ['https://example.com/ticket/123', QrScanRejection::Malformed, QrScanRejection::Malformed->message()],
        'the old racepack format' => ['UCR2027|UCCR-0320001|0320001', QrScanRejection::Malformed, QrScanRejection::Malformed->message()],
        'lower case' => ['uccr-0320001', QrScanRejection::Malformed, QrScanRejection::Malformed->message()],
        'an empty ID' => ['UCCR-0550001;', QrScanRejection::Malformed, QrScanRejection::Malformed->message()],
        'more IDs than a bundle has' => ['UCCR-0350001;UCCR-0350002;UCCR-0350003;UCCR-0350004;UCCR-0350005', QrScanRejection::Malformed, QrScanRejection::Malformed->message()],
        'a prefix that is not official' => ['UCCR-0360001', QrScanRejection::InvalidBib, QrScanRejection::InvalidBib->message()],
        'sequence 0000' => ['UCCR-0320000', QrScanRejection::InvalidBib, QrScanRejection::InvalidBib->message()],
        'a BIB nobody has' => ['UCCR-0329999', QrScanRejection::NotFound, QrScanRejection::NotFound->message()],
        'a participant record that is missing' => ['UCCR-0350001;UCCR-0350002;UCCR-0350003;UCCR-0350004', QrScanRejection::NotFound, QrScanRejection::NotFound->message()],
        'the same BIB twice' => ['UCCR-0550001;UCCR-0550001', QrScanRejection::DuplicateBib, QrScanRejection::DuplicateBib->message()],
        'participants of different registrations' => ['UCCR-0550001;UCCR-0320001', QrScanRejection::MixedRegistrations, QrScanRejection::MixedRegistrations->message()],
        'a bundle QR missing a participant' => ['UCCR-0550001', QrScanRejection::Incomplete, QrScanRejection::Incomplete->message('UCCR-0550001')],
        'a bundle QR in another order' => ['UCCR-0550002;UCCR-0550001', QrScanRejection::Incomplete, QrScanRejection::Incomplete->message('UCCR-0550001')],
        'an unpaid registration' => ['UCCR-0330007', QrScanRejection::Unpaid, 'Registrasi UCCR-0330007 belum lunas, sehingga BIB belum dapat diberikan.'],
    ]);

    it('refuses typed IDs that are unknown or unpaid', function (string $typed, string $message) {
        Registration::factory()->create(['code' => 'UCR-2027-UNPAY']);

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.scanner.store'), ['code' => $typed])
            ->assertJson(['tone' => 'error', 'message' => $message, 'modal' => null, 'row' => null]);

        expect(QrScan::query()->accepted()->exists())->toBeFalse();
    })->with([
        'unpaid' => ['UCR-2027-UNPAY', 'Registrasi UCR-2027-UNPAY belum lunas, sehingga BIB belum dapat diberikan.'],
        'unknown' => ['UCR-2027-NOPE1', QrScanRejection::NotFound->message()],
    ]);

    it('keeps only racepack IDs from refused scans, never other text a camera picked up', function () {
        $scan = fn (string $payload) => $this->actingAs($this->admin, 'admin')->postJson(route('admin.scanner.store'), ['payload' => $payload]);

        $scan('BEGIN:VCARD FN:Budi Santoso TEL:081234567890');
        $scan('UCCR-0329999');

        expect(QrScan::query()->orderBy('id')->pluck('payload')->all())->toBe([null, 'UCCR-0329999']);
    });

    it('logs a refused QR held in front of the camera once', function () {
        $scan = fn () => $this->actingAs($this->admin, 'admin')->postJson(route('admin.scanner.store'), ['payload' => 'UCCR-0329999']);

        $scan();
        $scan();
        $scan();

        expect(QrScan::query()->where('status', QrScanStatus::Rejected)->count())->toBe(1);
    });

    it('requires a QR payload or a registration ID', function () {
        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.scanner.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['payload' => 'Pindai QR code atau masukkan Registration ID.']);
    });
});

describe('scan details and access', function () {
    it('opens a recorded scan in the admin modal or as a page', function () {
        $scan = QrScan::factory()->for(paidBundle('couple', '0550001', ['Gita Permata']), 'registration')->create();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.scanner.show', $scan), ['X-Admin-Modal' => '1'])
            ->assertOk()
            ->assertSee(['QR Racepack Valid', 'Peserta 1 · Hendra Wijaya', 'Peserta 2 · Gita Permata', 'BIB 0550002'])
            ->assertDontSee('<html', false);

        $this->get(route('admin.scanner.show', $scan))->assertOk()->assertSee(['<html', 'QR Racepack Valid'], false);
    });

    it('does not open refused scans', function () {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.scanner.show', QrScan::factory()->rejected()->create()))
            ->assertNotFound();
    });

    it('sends the plain form without JavaScript to the scan details page', function () {
        paidRunner();

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.scanner.store'), ['code' => 'UCCR-0320001']);

        $response->assertRedirect(route('admin.scanner.show', QrScan::sole()))
            ->assertSessionHas('success', 'Successfully scanned UCCR-0320001. Your BIB Number is 0320001.');
    });

    it('keeps the scanner, its history and scan details from guests and participants', function (?Closure $actor) {
        $registration = paidRunner();
        $scan = QrScan::factory()->for($registration, 'registration')->create();

        if ($actor !== null) {
            $this->actingAs($actor());
        }

        $this->postJson(route('admin.scanner.store'), ['payload' => 'UCCR-0320001'])->assertUnauthorized();
        $this->get(route('admin.scanner.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.scanner.show', $scan))->assertRedirect(route('admin.login'));

        expect(QrScan::count())->toBe(1);
    })->with([
        'a guest' => [null],
        'a signed-in participant' => [fn () => User::factory()->create()],
    ]);
});
