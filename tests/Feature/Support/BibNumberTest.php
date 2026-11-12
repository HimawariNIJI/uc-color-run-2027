<?php

use App\Enums\FeePeriod;
use App\Enums\RaceCategory;
use App\Enums\RegistrationType;
use App\Exceptions\BibNumbersExhausted;
use App\Models\BibSequence;
use App\Models\Registration;
use App\Models\TicketPrice;
use App\Models\User;
use App\Support\BibNumber;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Every participant's BIB, payer first.
 *
 * @return list<string|null>
 */
function bibNumbers(Registration $registration): array
{
    $registration->refresh();

    return [$registration->bib_number, ...$registration->members()->pluck('bib_number')->all()];
}

it('uses the official prefix for every package', function (RegistrationType $type, ?FeePeriod $priceType, RaceCategory $category, string $prefix) {
    expect(TicketPrice::findOption($type, $priceType, $category)->bibPrefix())->toBe($prefix);
})->with([
    '3K Early Bird Internal' => [RegistrationType::Internal, FeePeriod::EarlyBird, RaceCategory::ThreeK, '031'],
    '3K Early Bird External' => [RegistrationType::External, FeePeriod::EarlyBird, RaceCategory::ThreeK, '032'],
    '3K Normal Internal' => [RegistrationType::Internal, FeePeriod::Regular, RaceCategory::ThreeK, '033'],
    '3K Normal External' => [RegistrationType::External, FeePeriod::Regular, RaceCategory::ThreeK, '034'],
    '3K Family Bundle' => [RegistrationType::Bundling, null, RaceCategory::ThreeK, '035'],
    '5K Early Bird Internal' => [RegistrationType::Internal, FeePeriod::EarlyBird, RaceCategory::FiveK, '051'],
    '5K Early Bird External' => [RegistrationType::External, FeePeriod::EarlyBird, RaceCategory::FiveK, '052'],
    '5K Normal Internal' => [RegistrationType::Internal, FeePeriod::Regular, RaceCategory::FiveK, '053'],
    '5K Normal External' => [RegistrationType::External, FeePeriod::Regular, RaceCategory::FiveK, '054'],
    '5K Couple Bundle' => [RegistrationType::Bundling, null, RaceCategory::FiveK, '055'],
    '10K Early Bird Internal' => [RegistrationType::Internal, FeePeriod::EarlyBird, RaceCategory::TenK, '101'],
    '10K Early Bird External' => [RegistrationType::External, FeePeriod::EarlyBird, RaceCategory::TenK, '102'],
    '10K Normal Internal' => [RegistrationType::Internal, FeePeriod::Regular, RaceCategory::TenK, '103'],
    '10K Normal External' => [RegistrationType::External, FeePeriod::Regular, RaceCategory::TenK, '104'],
]);

it('gives no BIB when registering, and the first 7-digit BIBs of the package once paid', function (string $package, array $expected) {
    $registration = registerForBib($this, $package);

    expect($registration->code)->toBe('UCCR-'.substr($expected[0], 0, 3).'-0001')
        ->and(bibNumbers($registration))->each->toBeNull()
        ->and(BibSequence::query()->find(substr($expected[0], 0, 3))->last_sequence)->toBe(0);

    $registration->markAsPaid();

    expect(bibNumbers($registration))->toBe($expected)
        ->each->toMatch('/^\d{7}$/');
})->with([
    '3K Early Bird Internal' => ['internal/early-bird/3k', ['0310001']],
    '3K Early Bird External' => ['external/early-bird/3k', ['0320001']],
    '3K Normal Internal' => ['internal/normal/3k', ['0330001']],
    '3K Normal External' => ['external/normal/3k', ['0340001']],
    '3K Family Bundle' => ['family', ['0350001', '0350002', '0350003', '0350004']],
    '5K Early Bird Internal' => ['internal/early-bird/5k', ['0510001']],
    '5K Early Bird External' => ['external/early-bird/5k', ['0520001']],
    '5K Normal Internal' => ['internal/normal/5k', ['0530001']],
    '5K Normal External' => ['external/normal/5k', ['0540001']],
    '5K Couple Bundle' => ['couple', ['0550001', '0550002']],
    '10K Early Bird Internal' => ['internal/early-bird/10k', ['1010001']],
    '10K Early Bird External' => ['external/early-bird/10k', ['1020001']],
    '10K Normal Internal' => ['internal/normal/10k', ['1030001']],
    '10K Normal External' => ['external/normal/10k', ['1040001']],
]);

it('gives each registration a code of its package that is never a BIB and never changes', function () {
    $codes = collect([
        'internal/early-bird/3k', 'internal/early-bird/3k', 'external/early-bird/3k',
        'internal/early-bird/3k', 'external/early-bird/3k', 'internal/normal/3k',
    ])->map(fn (string $package): Registration => registerForBib($this, $package));

    expect($codes->pluck('code')->all())->toBe(['UCCR-031-0001', 'UCCR-031-0002', 'UCCR-032-0001', 'UCCR-031-0003', 'UCCR-032-0002', 'UCCR-033-0001'])
        // Registering reserves no BIB numbers at all.
        ->and(BibSequence::query()->whereIn('prefix', ['031', '032', '033'])->orderBy('prefix')->pluck('last_sequence', 'prefix')->all())
        ->toBe(['031' => 0, '032' => 0, '033' => 0]);

    $codes[1]->markAsPaid();

    expect($codes[1]->fresh())
        ->code->toBe('UCCR-031-0002')
        ->bib_number->toBe('0310001');
});

it('gives the earlier BIB to whoever pays first, whoever registered first', function () {
    $registeredFirst = registerForBib($this, 'external/early-bird/5k');
    $this->travel(5)->minutes();
    $registeredSecond = registerForBib($this, 'external/early-bird/5k');

    $registeredSecond->markAsPaid();
    $this->travel(10)->minutes();
    $registeredFirst->markAsPaid();

    expect(bibNumbers($registeredSecond))->toBe(['0520001'])
        ->and(bibNumbers($registeredFirst))->toBe(['0520002']);
});

it('keeps an independent BIB sequence for every package', function () {
    $paidInOrder = collect(['internal/early-bird/3k', 'external/early-bird/3k', 'internal/early-bird/3k', 'couple', 'external/early-bird/3k', 'internal/early-bird/3k'])
        ->map(fn (string $package): Registration => registerForBib($this, $package))
        ->each->markAsPaid();

    expect($paidInOrder->map(fn (Registration $registration): array => bibNumbers($registration))->all())->toBe([
        ['0310001'], ['0320001'], ['0310002'], ['0550001', '0550002'], ['0320002'], ['0310003'],
    ]);
});

it('gives no BIB to a pending, expired or failed registration', function (string $state) {
    $registration = registerForBib($this, 'couple');

    match ($state) {
        'expired' => $this->travel(61)->minutes(),
        'failed' => $registration->markAsFailed(),
        default => null,
    };

    expect(bibNumbers($registration))->toBe([null, null])
        ->and(BibSequence::query()->find('055')->last_sequence)->toBe(0);
})->with(['pending', 'expired', 'failed']);

it('gives a Couple Bundle two consecutive numbers, in the order bundles pay', function () {
    $first = registerForBib($this, 'couple');
    $second = registerForBib($this, 'couple');
    $second->markAsPaid();
    $first->markAsPaid();

    expect(bibNumbers($second))->toBe(['0550001', '0550002'])
        ->and(bibNumbers($first))->toBe(['0550003', '0550004']);
});

it('gives a Family Bundle four consecutive numbers, even when an earlier family pays later', function () {
    $first = registerForBib($this, 'family');
    $second = registerForBib($this, 'family');
    $second->markAsPaid();
    $first->markAsPaid();

    expect(bibNumbers($second))->toBe(['0350001', '0350002', '0350003', '0350004'])
        ->and(bibNumbers($first))->toBe(['0350005', '0350006', '0350007', '0350008']);
});

it('issues BIBs exactly once, however often the payment is confirmed or the BIB page is opened', function () {
    $registration = registerForBib($this, 'couple');
    $registration->markAsPaid();
    $issued = bibNumbers($registration);

    $registration->fresh()->markAsPaid();
    $registration->fresh()->markAsPaid();
    $this->actingAs($registration->user)->get(route('my-bib.show'))->assertSeeInOrder($issued);
    $this->actingAs($registration->user)->get(route('my-bib.show'))->assertSeeInOrder($issued);

    expect(bibNumbers($registration))->toBe($issued)
        ->and(BibSequence::query()->find('055')->last_sequence)->toBe(2);
});

it('never hands out a number again, also after a payment retry', function () {
    $retried = registerForBib($this, 'external/early-bird/5k');
    $this->travel(61)->minutes();
    $other = registerForBib($this, 'external/early-bird/5k');
    $other->markAsPaid();

    $this->actingAs($retried->user)->post(route('payment.retry'))->assertRedirect(route('payment.show'));
    $retried->fresh()->markAsPaid();

    expect(bibNumbers($other))->toBe(['0520001'])
        ->and(bibNumbers($retried))->toBe(['0520002'])
        ->and(Registration::query()->whereNotNull('bib_number')->pluck('bib_number')->duplicates())->toBeEmpty();
});

it('gives nothing back and skips nothing when the registration cannot be saved', function () {
    Registration::creating(function (Registration $registration): void {
        if ($registration->full_name === 'Gagal Tersimpan') {
            throw new RuntimeException('Database unavailable');
        }
    });

    $this->withoutExceptionHandling();
    expect(fn () => $this->actingAs(User::factory()->ucEmail()->create())
        ->post(route('registration.store', ['internal', 'early-bird', '3k']), registrationPayload(['full_name' => 'Gagal Tersimpan'])))
        ->toThrow(RuntimeException::class, 'Database unavailable');

    expect(BibSequence::query()->find('031')->last_sequence)->toBe(0)
        ->and(registerForBib($this, 'internal/early-bird/3k')->code)->toBe('UCCR-031-0001');
});

it('never gives two simultaneous payments the same number', function () {
    TicketPrice::query()->count(); // Prepare the test database before adding the extra connections.
    $file = tempnam(sys_get_temp_dir(), 'bib');
    $connection = ['driver' => 'sqlite', 'database' => $file, 'prefix' => '', 'foreign_key_constraints' => true, 'busy_timeout' => 1];
    config(['database.connections.bib_one' => $connection, 'database.connections.bib_two' => $connection]);
    $default = DB::getDefaultConnection();

    try {
        Schema::connection('bib_one')->create('bib_sequences', function ($table) {
            $table->char('prefix', 3)->primary();
            $table->unsignedSmallInteger('last_sequence')->default(0);
            $table->timestamps();
        });
        DB::connection('bib_one')->table('bib_sequences')->insert(['prefix' => '031', 'last_sequence' => 0]);

        // Payment one draws two numbers (a Couple Bundle) and has not committed yet.
        DB::setDefaultConnection('bib_one');
        DB::connection('bib_one')->beginTransaction();
        $first = BibNumber::reserve('031', 2);

        // Payment two, on its own connection, cannot read the sequence past that lock.
        DB::setDefaultConnection('bib_two');
        expect(fn () => DB::connection('bib_two')->transaction(fn () => BibNumber::reserve('031')))
            ->toThrow(QueryException::class, 'database is locked');

        DB::setDefaultConnection('bib_one');
        DB::connection('bib_one')->commit();

        // Once payment one has committed, payment two continues after its numbers.
        DB::setDefaultConnection('bib_two');
        $second = DB::connection('bib_two')->transaction(fn () => BibNumber::reserve('031'));
    } finally {
        DB::setDefaultConnection($default);
        DB::purge('bib_one');
        DB::purge('bib_two');
        @unlink($file);
    }

    expect($first)->toBe(['0310001', '0310002'])
        ->and($second)->toBe(['0310003']);
});

it('only hands out numbers and codes inside a database transaction', function () {
    DB::rollBack(); // Leave the test's own transaction for a moment.

    try {
        expect(fn () => BibNumber::reserve('031'))->toThrow(LogicException::class)
            ->and(fn () => BibNumber::nextRegistrationCode('031'))->toThrow(LogicException::class);
    } finally {
        DB::beginTransaction();
    }
});

it('stops at 9999 and closes the package with a clear message instead of making an invalid BIB', function () {
    BibSequence::query()->whereKey('031')->update(['last_sequence' => 9998]);

    $last = registerForBib($this, 'internal/early-bird/3k');
    $last->markAsPaid();

    expect(bibNumbers($last))->toBe(['0319999']);

    $this->actingAs(User::factory()->ucEmail()->create())
        ->post(route('registration.store', ['internal', 'early-bird', '3k']), registrationPayload())
        ->assertRedirect(route('registration.category', ['internal', 'early-bird']))
        ->assertSessionHas('error', 'Nomor BIB untuk paket 3K Early Bid (Internal) sudah habis (maksimal 9999 peserta), jadi pendaftaran paket ini ditutup. Silakan pilih paket lain atau hubungi panitia.');

    expect(Registration::count())->toBe(1)
        ->and(BibSequence::query()->find('031')->last_sequence)->toBe(9999)
        ->and(Storage::disk('identity_photos')->allFiles())->toHaveCount(1)
        ->and(fn () => BibNumber::make('031', 10000))->toThrow(InvalidArgumentException::class);
});

it('refuses a bundle when fewer numbers are left than it has participants', function () {
    BibSequence::query()->whereKey('055')->update(['last_sequence' => 9998]);

    $this->actingAs(User::factory()->create())
        ->post(route('registration.bundle.store', 'couple'), registrationPayload(['members' => [bundleMember()]]))
        ->assertRedirect(route('registration.price', 'bundling'))
        ->assertSessionHas('error');

    expect(Registration::count())->toBe(0)
        ->and(BibSequence::query()->find('055')->last_sequence)->toBe(9998)
        ->and(fn () => BibNumber::reserve('055', 2))->toThrow(BibNumbersExhausted::class);
});

it('still confirms a payment when the numbers ran out after registering, and reports it for the committee', function () {
    Exceptions::fake();
    $registration = registerForBib($this, 'couple');
    BibSequence::query()->whereKey('055')->update(['last_sequence' => 9998]);

    $registration->markAsPaid();

    expect($registration->fresh()->isPaid())->toBeTrue()
        ->and(bibNumbers($registration))->toBe([null, null])
        ->and(BibSequence::query()->find('055')->last_sequence)->toBe(9998);
    Exceptions::assertReported(BibNumbersExhausted::class);
});

it('makes and checks 7-digit BIBs only', function () {
    expect(BibNumber::make('031', 1))->toBe('0310001')
        ->and(BibNumber::make('104', 9999))->toBe('1049999')
        ->and(BibNumber::isValid('0350004'))->toBeTrue()
        ->and(BibNumber::isValid('0301020001'))->toBeFalse()
        ->and(BibNumber::isValid('0360001'))->toBeFalse()
        ->and(BibNumber::isValid('0310000'))->toBeFalse()
        ->and(fn () => BibNumber::make('999', 1))->toThrow(InvalidArgumentException::class)
        ->and(fn () => BibNumber::make('031', 0))->toThrow(InvalidArgumentException::class);
});

/**
 * Run $callback against a separate database that holds the columns the BIB migration touches, in
 * the 10-digit format and filled with $registrations / $members. SQLite rebuilds a table to change
 * a column, which the test's own transaction would not allow; MySQL changes the column in place.
 *
 * @param  list<array<string, mixed>>  $registrations
 * @param  list<array<string, mixed>>  $members
 */
function withTenDigitBibData(array $registrations, array $members, Closure $callback): mixed
{
    TicketPrice::query()->count(); // Prepare the test database before adding the extra connection.
    config(['database.connections.bib_migration' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
    $default = DB::getDefaultConnection();

    try {
        DB::setDefaultConnection('bib_migration');

        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('bib_number')->nullable()->unique();
        });
        Schema::create('registration_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->string('bib_number', 10)->nullable()->unique();
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->string('order_id', 50)->unique();
        });

        DB::table('registrations')->insert($registrations);
        DB::table('registration_members')->insert($members);

        return $callback(require database_path('migrations/2026_11_11_164603_convert_bib_numbers_to_seven_digits.php'));
    } finally {
        DB::setDefaultConnection($default);
        DB::purge('bib_migration');
    }
}

it('converts existing 10-digit codes and BIBs to the new format without changing their sequence', function () {
    $result = withTenDigitBibData(
        [
            ['id' => 1, 'code' => 'UCCR-0301020001', 'bib_number' => '0301020001'], // paid 3K Early Bird External, number 1
            ['id' => 2, 'code' => 'UCCR-0300040002', 'bib_number' => '0300040002'], // paid 3K Family Bundle
            ['id' => 3, 'code' => 'UCCR-1002010007', 'bib_number' => null],         // unpaid 10K Normal Internal
            ['id' => 4, 'code' => 'UCR-2027-ABCDE', 'bib_number' => null],          // an older format, left alone
        ],
        [['registration_id' => 2, 'position' => 2, 'bib_number' => '0300040003']],
        function (object $migration): array {
            DB::table('payments')->insert(['registration_id' => 1, 'order_id' => 'UCCR-0301020001-1']);
            $migration->up();

            $converted = [
                'registrations' => DB::table('registrations')->orderBy('id')->get(['code', 'reserved_bib_number', 'bib_number'])->map(fn (object $row): array => (array) $row)->all(),
                'member' => DB::table('registration_members')->value('bib_number'),
                'order_id' => DB::table('payments')->value('order_id'),
                'sequences' => DB::table('bib_sequences')->whereIn('prefix', ['031', '032', '035', '103'])->orderBy('prefix')->pluck('last_sequence', 'prefix')->map(fn (mixed $sequence): int => (int) $sequence)->all(),
                'prefixes' => DB::table('bib_sequences')->count(),
            ];

            $migration->down();

            return [...$converted, 'restored' => DB::table('registrations')->orderBy('id')->pluck('bib_number', 'code')->all()];
        },
    );

    expect($result['registrations'])->toBe([
        ['code' => 'UCCR-0320001', 'reserved_bib_number' => '0320001', 'bib_number' => '0320001'],
        ['code' => 'UCCR-0350002', 'reserved_bib_number' => '0350002', 'bib_number' => '0350002'],
        ['code' => 'UCCR-1030007', 'reserved_bib_number' => '1030007', 'bib_number' => null],
        ['code' => 'UCR-2027-ABCDE', 'reserved_bib_number' => null, 'bib_number' => null],
    ])
        ->and($result['member'])->toBe('0350003')
        // Midtrans knows the payment by its order ID, so it keeps the old code.
        ->and($result['order_id'])->toBe('UCCR-0301020001-1')
        // Every prefix gets a sequence that continues after the highest number already used.
        ->and($result['prefixes'])->toBe(14)
        ->and($result['sequences'])->toBe(['031' => 0, '032' => 1, '035' => 3, '103' => 7])
        // Rolling back restores the 10-digit format exactly.
        ->and($result['restored'])->toBe([
            'UCCR-0301020001' => '0301020001',
            'UCCR-0300040002' => '0300040002',
            'UCCR-1002010007' => null,
            'UCR-2027-ABCDE' => null,
        ]);
});

it('changes nothing when an existing 10-digit number has a prefix that is not an official package', function () {
    $result = withTenDigitBibData(
        [['id' => 1, 'code' => 'UCCR-0399990001', 'bib_number' => null]],
        [],
        function (object $migration): array {
            $error = null;

            try {
                $migration->up();
            } catch (RuntimeException $exception) {
                $error = $exception->getMessage();
            }

            return ['error' => $error, 'code' => DB::table('registrations')->value('code'), 'sequences' => Schema::hasTable('bib_sequences')];
        },
    );

    expect($result['error'])->toContain('cannot be converted. Nothing was changed.')
        ->and($result['code'])->toBe('UCCR-0399990001')
        ->and($result['sequences'])->toBeFalse();
});
