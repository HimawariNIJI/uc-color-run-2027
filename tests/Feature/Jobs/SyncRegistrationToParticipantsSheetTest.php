<?php

use App\Enums\PaymentStatus;
use App\Exceptions\GoogleSheetsException;
use App\Jobs\SyncRegistrationToParticipantsSheet;
use App\Models\Admin;
use App\Models\Registration;
use App\Models\RegistrationMember;
use App\Support\ParticipantsSheet;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Tests\Fakes\FakeGoogleSheets;

/**
 * The spreadsheet value of the current Jakarta time.
 */
function sheetNow(): float
{
    return sheetDate(now('Asia/Jakarta')->format('Y-m-d H:i:s'));
}

/**
 * Report a Midtrans status for the registration's latest QRIS through a signed notification.
 */
function notifyMidtrans(Registration $registration, string $transactionStatus, array $extra = []): void
{
    midtransReports($transactionStatus, $extra);

    test()->postJson(
        route('payments.midtrans.notification'),
        midtransNotification($registration->latestPayment()->first(), $transactionStatus, $transactionStatus === 'settlement' ? '200' : '202'),
    )->assertOk();
}

/**
 * Scan a racepack QR at the admin scanner and return the response.
 */
function scanRacepack(string $payload): TestResponse
{
    $admin = Admin::query()->firstWhere('name', 'Panitia Racepack') ?? Admin::factory()->create(['name' => 'Panitia Racepack']);

    return test()->actingAs($admin, 'admin')->postJson(route('admin.scanner.store'), ['payload' => $payload])->assertOk();
}

describe('queueing', function () {
    it('queues one sync of the registration once the transaction that changed it commits', function () {
        FakeGoogleSheets::install();
        Queue::fake();

        $registration = DB::transaction(function (): Registration {
            $registration = Registration::factory()->bundle('couple')->create();
            RegistrationMember::factory()->for($registration)->create(['position' => 2]);
            $registration->forceFill(['phone' => '081200000000'])->save();

            Queue::assertNothingPushed();

            return $registration;
        });

        Queue::assertPushed(SyncRegistrationToParticipantsSheet::class, 1);
        Queue::assertPushed(fn (SyncRegistrationToParticipantsSheet $job): bool => $job->registrationId === $registration->id && $job->delay !== null);
    });

    it('queues nothing while Google Sheets is not configured', function () {
        Queue::fake();

        Registration::factory()->paid()->create();

        Queue::assertNothingPushed();
    });

    it('queues nothing when a transaction is rolled back', function () {
        FakeGoogleSheets::install();
        Queue::fake();

        rescue(fn () => DB::transaction(function (): never {
            Registration::factory()->create();

            throw new RuntimeException('Rolled back.');
        }), report: false);

        Queue::assertNothingPushed();
    });
});

describe('keeping the sheet up to date', function () {
    it('follows an individual participant from registration through payment to the racepack scan, on one row', function () {
        $this->freezeSecond();
        $google = FakeGoogleSheets::install(withParticipantsTab: true);

        $registration = registerForBib($this, 'external/early-bird/3k');

        // No BIB yet, so the row's ID is the registration code and the position.
        expect($google->participants())->toHaveCount(1)
            ->and($google->participant('UCCR-032-0001-1'))->toMatchArray([
                'REGISTRATION CODE' => 'UCCR-032-0001', 'PAYER NAME' => 'Budi Santoso', 'FULL NAME' => 'Budi Santoso', 'RACE CATEGORY' => '3K',
                'REGISTRATION TYPE' => 'External', 'PRICE TYPE' => 'Early Bird', 'PAYMENT STATUS' => 'Pending', 'REGISTRATION DATE' => sheetNow(),
                'PAYMENT DATE' => '', 'BIB' => '', 'BIB QR' => '', 'QR SCAN STATUS' => 'Not Scanned',
            ]);

        $registration->startQrisPayment();
        notifyMidtrans($registration, 'settlement', ['settlement_time' => '2026-11-09 11:05:00']);

        expect($google->participants())->toHaveCount(1)
            ->and($google->participant('UCCR-0320001'))->toMatchArray([
                'REGISTRATION CODE' => 'UCCR-032-0001',
                'PAYMENT STATUS' => 'Settlement', 'PAYMENT DATE' => sheetDate('2026-11-09 11:05:00'),
                'PAYMENT TRANSACTION ID' => $registration->latestPayment()->first()->gateway_transaction_id,
                'BIB' => '0320001', 'BIB QR' => 'UCCR-0320001', 'QR SCAN STATUS' => 'Not Scanned',
            ]);

        $this->travel(2)->hours();
        scanRacepack('UCCR-0320001')->assertJson(['tone' => 'success']);

        expect($google->participants())->toHaveCount(1)
            ->and($google->participant('UCCR-0320001'))->toMatchArray([
                'PAYMENT STATUS' => 'Settlement', 'BIB' => '0320001',
                'QR SCAN STATUS' => 'Scanned', 'QR SCANNED AT' => sheetNow(), 'QR SCANNED BY' => 'Panitia Racepack',
            ]);
    });

    it('follows a family bundle on four rows, and one bundle scan marks all four as scanned', function () {
        $google = FakeGoogleSheets::install(withParticipantsTab: true);

        $registration = registerForBib($this, 'family');
        $rows = collect($google->participants());

        expect($rows)->toHaveCount(4)
            ->and($rows->pluck('FULL NAME')->all())->toBe(['Budi Santoso', 'Indah Wijaya', 'Joko Wijaya', 'Kartika Wijaya'])
            ->and($rows->pluck('REGISTRATION CODE')->unique()->all())->toBe(['UCCR-035-0001'])
            ->and($rows->pluck('BIB')->unique()->all())->toBe([''])
            ->and($rows->pluck('AMOUNT')->filter(fn (mixed $amount): bool => $amount !== '')->all())->toBe([(int) $registration->amount])
            ->and($rows->pluck('PAYMENT STATUS')->unique()->all())->toBe(['Pending']);

        $registration->startQrisPayment();
        notifyMidtrans($registration, 'settlement');
        $rows = collect($google->participants());

        expect($rows)->toHaveCount(4)
            ->and($rows->pluck('REGISTRATION ID')->all())->toBe(['UCCR-0350001', 'UCCR-0350002', 'UCCR-0350003', 'UCCR-0350004'])
            ->and($rows->pluck('BIB')->all())->toBe(['0350001', '0350002', '0350003', '0350004'])
            ->and($rows->pluck('PAYMENT STATUS')->unique()->all())->toBe(['Settlement'])
            ->and($rows->pluck('BIB QR')->unique()->all())->toBe(['UCCR-0350001;UCCR-0350002;UCCR-0350003;UCCR-0350004']);

        scanRacepack($rows->first()['BIB QR'])->assertJson(['tone' => 'success']);
        $rows = collect($google->participants());

        expect($rows)->toHaveCount(4)
            ->and($rows->pluck('QR SCAN STATUS')->all())->toBe(array_fill(0, 4, 'Scanned'))
            ->and($rows->pluck('QR SCANNED BY')->unique()->all())->toBe(['Panitia Racepack']);
    });

    it('shows a QRIS that expired, was cancelled or was refused on every participant row', function (string $midtransStatus, string $sheetStatus) {
        $google = FakeGoogleSheets::install(withParticipantsTab: true);
        $registration = registerForBib($this, 'couple');
        $registration->startQrisPayment();

        notifyMidtrans($registration, $midtransStatus);

        expect($registration->fresh()->payment_status)->toBe(PaymentStatus::Failed)
            ->and(collect($google->participants())->pluck('PAYMENT STATUS')->all())->toBe([$sheetStatus, $sheetStatus]);
    })->with([
        'expired' => ['expire', 'Expired'],
        'cancelled' => ['cancel', 'Cancelled'],
        'denied' => ['deny', 'Failed'],
    ]);

    it('keeps a couple on the same two rows through an expired payment, a retry and the settlement', function () {
        $google = FakeGoogleSheets::install(withParticipantsTab: true);
        $registration = registerForBib($this, 'couple');

        notifyMidtrans($registration, 'expire');

        expect(collect($google->participants())->pluck('PAYMENT STATUS', 'REGISTRATION ID')->all())->toBe(['UCCR-055-0001-1' => 'Expired', 'UCCR-055-0001-2' => 'Expired'])
            ->and(collect($google->participants())->pluck('BIB')->unique()->all())->toBe(['']);

        $this->actingAs($registration->user)->post(route('payment.retry'))->assertRedirect(route('payment.show'));
        $retry = $registration->latestPayment()->first();

        expect($retry->order_id)->toBe('UCCR-055-0001-2')
            ->and($google->participants())->toHaveCount(2)
            ->and(collect($google->participants())->pluck('PAYMENT STATUS')->unique()->all())->toBe(['Pending'])
            ->and(collect($google->participants())->pluck('PAYMENT TRANSACTION ID')->unique()->all())->toBe([$retry->gateway_transaction_id]);

        notifyMidtrans($registration, 'settlement', ['settlement_time' => '2026-11-09 12:00:00']);
        $rows = collect($google->participants());

        expect($rows)->toHaveCount(2)
            ->and($rows->pluck('REGISTRATION ID')->all())->toBe(['UCCR-0550001', 'UCCR-0550002'])
            ->and($rows->pluck('REGISTRATION CODE')->unique()->all())->toBe(['UCCR-055-0001'])
            ->and($rows->pluck('FULL NAME')->all())->toBe(['Budi Santoso', 'Siti Santoso'])
            ->and($rows->pluck('PAYMENT STATUS')->unique()->all())->toBe(['Settlement'])
            ->and($rows->pluck('PAYMENT DATE')->unique()->all())->toBe([sheetDate('2026-11-09 12:00:00')])
            ->and($rows->pluck('PAYMENT TRANSACTION ID')->unique()->all())->toBe([$retry->gateway_transaction_id])
            ->and($rows->pluck('BIB')->all())->toBe(['0550001', '0550002'])
            ->and($rows->pluck('BIB QR')->unique()->all())->toBe(['UCCR-0550001;UCCR-0550002']);
    });

    it('shows a retry that fails as well on the same row, without a BIB', function () {
        $google = FakeGoogleSheets::install(withParticipantsTab: true);
        $registration = registerForBib($this, 'external/early-bird/3k');
        notifyMidtrans($registration, 'expire');

        $this->actingAs($registration->user)->post(route('payment.retry'))->assertRedirect(route('payment.show'));
        notifyMidtrans($registration, 'deny');

        expect($google->participants())->toHaveCount(1)
            ->and($google->participant('UCCR-032-0001-1'))->toMatchArray([
                'PAYMENT STATUS' => 'Failed', 'PAYMENT DATE' => '', 'BIB' => '', 'BIB QR' => '',
                'PAYMENT TRANSACTION ID' => $registration->latestPayment()->first()->gateway_transaction_id,
            ]);
    });

    it('keeps Settlement when a repeated or late failure notification arrives after the payment', function () {
        $google = FakeGoogleSheets::install(withParticipantsTab: true);
        $registration = registerForBib($this, 'external/early-bird/3k');
        $registration->startQrisPayment();

        notifyMidtrans($registration, 'settlement');
        notifyMidtrans($registration, 'settlement');
        notifyMidtrans($registration, 'expire');
        notifyMidtrans($registration, 'cancel');

        expect($google->participants())->toHaveCount(1)
            ->and($google->participant('UCCR-0320001'))->toMatchArray(['PAYMENT STATUS' => 'Settlement', 'BIB' => '0320001']);
    });

    it('writes nothing for a refused scan', function () {
        $google = FakeGoogleSheets::install(withParticipantsTab: true);
        registerForBib($this, 'external/early-bird/3k');
        $writes = count($google->writes());

        scanRacepack('UCCR-0320001')->assertJson(['tone' => 'error']);
        scanRacepack('https://example.com/not-a-racepack')->assertJson(['tone' => 'error']);

        expect($google->writes())->toHaveCount($writes)
            ->and($google->participant('UCCR-032-0001-1')['QR SCAN STATUS'])->toBe('Not Scanned');
    });
});

describe('when Google Sheets is down or refuses', function () {
    it('never stops a registration, payment or racepack scan, and sheets:sync catches up afterwards', function () {
        Exceptions::fake();
        Log::spy();
        $google = FakeGoogleSheets::install(withParticipantsTab: true);
        $google->failNext(503, 500);

        $registration = registerForBib($this, 'external/early-bird/3k');
        $registration->startQrisPayment();
        notifyMidtrans($registration, 'settlement');
        scanRacepack('UCCR-0320001')->assertJson(['tone' => 'success']);

        expect($registration->fresh())->payment_status->toBe(PaymentStatus::Paid)->bib_number->toBe('0320001')->bib_collected_at->not->toBeNull()
            ->and($google->participants())->toBe([]);
        Exceptions::assertReported(fn (GoogleSheetsException $exception): bool => $exception->status === 503);

        $google->recover();
        $this->artisan('sheets:sync')->assertSuccessful();

        expect($google->participants())->toHaveCount(1)
            ->and($google->participant('UCCR-0320001'))->toMatchArray(['PAYMENT STATUS' => 'Settlement', 'BIB' => '0320001', 'QR SCAN STATUS' => 'Scanned']);
    });

    it('tries again later after a rate limit, a Google server error or a lost connection', function (Closure $breakGoogle) {
        $registration = Registration::factory()->create();
        $google = FakeGoogleSheets::install(withParticipantsTab: true);
        $breakGoogle($google);
        $job = (new SyncRegistrationToParticipantsSheet($registration->id))->withFakeQueueInteractions();

        expect(fn () => $job->handle())->toThrow(GoogleSheetsException::class);

        $job->assertNotFailed();
    })->with([
        'rate limit' => [fn (FakeGoogleSheets $google) => $google->failNext(429)],
        'server error' => [fn (FakeGoogleSheets $google) => $google->failNext(503)],
        'lost connection' => [fn (FakeGoogleSheets $google) => $google->loseConnectionNext()],
    ]);

    it('gives up at once on problems someone has to fix first', function (int $status) {
        $registration = Registration::factory()->create();
        $google = FakeGoogleSheets::install(withParticipantsTab: true);
        $google->failNext($status);
        $job = (new SyncRegistrationToParticipantsSheet($registration->id))->withFakeQueueInteractions();

        $job->handle();

        $job->assertFailedWith(GoogleSheetsException::class);
    })->with([
        'no permission' => [403],
        'spreadsheet not found' => [404],
    ]);

    it('gives up at once when the key file is missing', function () {
        $registration = Registration::factory()->create();
        FakeGoogleSheets::install();
        config(['services.google_sheets.credentials' => 'secrets/missing.json']);
        $job = (new SyncRegistrationToParticipantsSheet($registration->id))->withFakeQueueInteractions();

        $job->handle();

        $job->assertFailedWith(GoogleSheetsException::class);
    });

    it('logs only the registration number and Google\'s short error when a sync fails, never participant data', function () {
        Log::spy();
        $registration = Registration::factory()->create(['full_name' => 'Rina Wijaya', 'medical_history' => 'Asma ringan', 'drug_allergies' => 'Penisilin']);

        (new SyncRegistrationToParticipantsSheet($registration->id))->failed(new GoogleSheetsException('Google Sheets answered HTTP 403: The caller does not have permission', 403));

        Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message, array $context): bool => str_contains($message, "registration #{$registration->id}")
            && str_contains($message, 'php artisan sheets:sync')
            && $context === ['error' => 'Google Sheets answered HTTP 403: The caller does not have permission']);
    });
});

describe('the sync job', function () {
    it('lets one sync at a time touch the sheet and retries with growing pauses for a day', function () {
        $this->freezeSecond();
        $job = new SyncRegistrationToParticipantsSheet(1);

        expect($job->middleware())->toHaveCount(1)
            ->and($job->middleware()[0])->toBeInstanceOf(WithoutOverlapping::class)->key->toBe('participants-sheet')
            ->and($job->backoff())->toBe([30, 60, 120, 300, 600])
            ->and($job->retryUntil()->getTimestamp())->toBe(now()->addDay()->getTimestamp())
            ->and($job->maxExceptions)->toBe(5);
    });

    it('does nothing for a registration deleted before it ran', function () {
        $google = FakeGoogleSheets::install(withParticipantsTab: true);

        (new SyncRegistrationToParticipantsSheet(999999))->handle();

        expect($google->calls)->toBe([]);
    });
});

describe('sheets:initialize', function () {
    it('prepares the Participants tab', function () {
        $google = FakeGoogleSheets::install();

        $this->artisan('sheets:initialize')
            ->expectsOutputToContain('Created the Participants tab.')
            ->expectsOutputToContain('Wrote the header row.')
            ->assertSuccessful();

        expect($google->tabs['Participants']['rows'])->toBe([ParticipantsSheet::HEADERS]);
    });

    it('only checks access and the tab with --check, changing nothing', function () {
        $google = FakeGoogleSheets::install(withParticipantsTab: true);

        $this->artisan('sheets:initialize --check')
            ->expectsOutputToContain('The service account signed in and can open the spreadsheet.')
            ->expectsOutputToContain('missing or different')
            ->assertSuccessful();

        expect($google->writes())->toBe([])
            ->and($google->tabs['Participants']['rows'])->toBe([]);
    });

    it('explains a configuration problem without printing anything from the key file', function () {
        FakeGoogleSheets::install();
        $path = tempnam(sys_get_temp_dir(), 'key');
        file_put_contents($path, json_encode(['type' => 'authorized_user', 'client_secret' => 'SECRET-VALUE-123', 'refresh_token' => 'REFRESH-SECRET']));
        config(['services.google_sheets.credentials' => $path]);

        $this->artisan('sheets:initialize')
            ->expectsOutputToContain('is not a service account key')
            ->doesntExpectOutputToContain('SECRET')
            ->assertFailed();
    });

    it('tells the committee to share the spreadsheet with the service account when Google refuses access', function () {
        $google = FakeGoogleSheets::install();
        $google->failNext(403);

        $this->artisan('sheets:initialize')
            ->expectsOutputToContain('Share it with the client_email from the key file as Editor.')
            ->assertFailed();
    });
});

describe('sheets:sync', function () {
    it('writes every registration without adding duplicates when run again', function () {
        Registration::factory()->create(['code' => 'UCCR-032-0001']);
        Registration::factory()->paid()->create(['code' => 'UCCR-032-0002', 'bib_number' => '0320001']);
        $google = FakeGoogleSheets::install(withParticipantsTab: true);

        $this->artisan('sheets:sync')->expectsOutputToContain('Wrote 2 registration(s): 0 row(s) updated, 2 row(s) added.')->assertSuccessful();
        $this->artisan('sheets:sync')->expectsOutputToContain('Wrote 2 registration(s): 2 row(s) updated, 0 row(s) added.')->assertSuccessful();

        expect(collect($google->participants())->pluck('PAYMENT STATUS', 'REGISTRATION ID')->all())->toBe(['UCCR-032-0001-1' => 'Pending', 'UCCR-0320001' => 'Settlement']);
    });

    it('writes only the registrations asked for and names codes it does not know', function () {
        Registration::factory()->create(['code' => 'UCCR-032-0001']);
        Registration::factory()->create(['code' => 'UCCR-032-0002']);
        $google = FakeGoogleSheets::install(withParticipantsTab: true);

        $this->artisan('sheets:sync UCCR-032-0002 UCCR-032-9999')
            ->expectsOutputToContain('No registration with code: UCCR-032-9999')
            ->expectsOutputToContain('Wrote 1 registration(s)')
            ->assertSuccessful();

        expect(collect($google->participants())->pluck('REGISTRATION ID')->all())->toBe(['UCCR-032-0002-1']);
    });

    it('queues one job per registration with --queue', function () {
        $registrations = Registration::factory()->count(3)->create();
        FakeGoogleSheets::install(withParticipantsTab: true);
        Queue::fake();

        $this->artisan('sheets:sync --queue')->expectsOutputToContain('Queued 3 registration(s).')->assertSuccessful();

        Queue::assertPushed(SyncRegistrationToParticipantsSheet::class, 3);
        Queue::assertPushed(fn (SyncRegistrationToParticipantsSheet $job): bool => $job->registrationId === $registrations->last()->id);
    });

    it('refuses to run while Google Sheets is not configured', function () {
        $this->artisan('sheets:sync')->expectsOutputToContain('GOOGLE_SHEETS_SPREADSHEET_ID is empty.')->assertFailed();
    });
});
