<?php

use App\Enums\PaymentStatus;
use App\Models\BibSequence;
use App\Models\Registration;
use App\Models\TicketPrice;
use App\Support\QrisCheckout;
use Database\Seeders\TicketPriceSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Two requests at the same moment, as two connections ("first" and "second") to one SQLite file
 * with the application's tables and packages. Whatever "first" changes inside its open
 * transaction holds the database's write lock until it commits.
 *
 * SQLite allows one writer at a time, and busy_timeout is 1 ms, so a "second" write that would
 * have to wait fails at once with "database is locked". MySQL makes the second request wait for
 * the row locks instead. Either way the second request cannot act on what it read before the
 * first committed, and after the commit it sees the first request's result.
 */
function withTwoRequests(Closure $callback): void
{
    TicketPrice::query()->count(); // Prepare the test database before adding the extra connections.
    $file = tempnam(sys_get_temp_dir(), 'payments');
    $connection = ['driver' => 'sqlite', 'database' => $file, 'prefix' => '', 'foreign_key_constraints' => true, 'busy_timeout' => 1];
    config(['database.connections.first' => $connection, 'database.connections.second' => $connection]);
    $default = DB::getDefaultConnection();

    try {
        Artisan::call('migrate', ['--database' => 'first', '--force' => true]);
        DB::setDefaultConnection('first');
        (new TicketPriceSeeder)->run();

        $callback();
    } finally {
        DB::setDefaultConnection($default);
        DB::purge('first');
        DB::purge('second');
        @unlink($file);
    }
}

/**
 * Make the next steps run as the given request, and return the registration as that request loads it.
 */
function asRequest(string $connection, Registration $registration): Registration
{
    DB::setDefaultConnection($connection);

    return Registration::on($connection)->findOrFail($registration->getKey());
}

it('lets only one of two simultaneous retries take the last slot of a package', function () {
    withTwoRequests(function () {
        setPackage('external', 'early_bird', '5k', ['quota' => 1]);
        $one = Registration::factory()->failed()->create(['type' => 'external', 'category' => '5k']);
        $two = Registration::factory()->failed()->create(['type' => 'external', 'category' => '5k']);

        DB::connection('first')->beginTransaction();
        expect(QrisCheckout::retry(asRequest('first', $one)))->toBeNull();

        // While the first retry has not committed, the second cannot claim the slot it read as free.
        expect(fn () => QrisCheckout::retry(asRequest('second', $two)))->toThrow(QueryException::class, 'database is locked');

        DB::connection('first')->commit();

        // Once it has, the second retry sees the slot taken and refuses.
        expect(QrisCheckout::retry(asRequest('second', $two)))->toStartWith('Kuota paket 5K Early Bid (External) sudah habis');

        DB::setDefaultConnection('first');
        expect(Registration::query()->holdingSlot()->pluck('id')->all())->toBe([$one->id])
            ->and($two->fresh()->payment_status)->toBe(PaymentStatus::Failed)
            ->and($two->payments()->count())->toBe(0)
            ->and($one->payments()->count())->toBe(1);
    });
});

it('never gives two simultaneous settlements of a package the same BIB', function () {
    withTwoRequests(function () {
        $one = Registration::factory()->bundle('couple')->create();
        $two = Registration::factory()->bundle('couple')->create();

        foreach ([$one, $two] as $registration) {
            $registration->members()->create(['position' => 2, 'full_name' => 'Gita Permata', 'birth_date' => '2000-01-01', 'gender' => 'female', 'tshirt_size' => 'S']);
        }

        DB::connection('first')->beginTransaction();
        asRequest('first', $one)->markAsPaid();

        expect(fn () => asRequest('second', $two)->markAsPaid())->toThrow(QueryException::class, 'database is locked');

        DB::connection('first')->commit();
        asRequest('second', $two)->markAsPaid();

        DB::setDefaultConnection('first');
        expect($one->fresh()->participantBibNumbers())->toBe(['0550001', '0550002'])
            ->and($two->fresh()->participantBibNumbers())->toBe(['0550003', '0550004'])
            ->and(BibSequence::query()->find('055')->last_sequence)->toBe(4);
    });
});

it('issues BIBs once when the same settlement is confirmed twice at the same moment', function () {
    withTwoRequests(function () {
        $registration = Registration::factory()->create(['type' => 'internal', 'category' => '3k']);

        DB::connection('first')->beginTransaction();
        asRequest('first', $registration)->markAsPaid();

        expect(fn () => asRequest('second', $registration)->markAsPaid())->toThrow(QueryException::class, 'database is locked');

        DB::connection('first')->commit();

        // Midtrans repeats an unanswered notification; the repeat finds the payment already confirmed.
        asRequest('second', $registration)->markAsPaid();

        DB::setDefaultConnection('first');
        expect($registration->fresh()->bib_number)->toBe('0310001')
            ->and(BibSequence::query()->find('031')->last_sequence)->toBe(1);
    });
});
