<?php

namespace Database\Seeders\Foundation;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds the database foundation built from the "Database Structure UC Color Run 2027" PDF.
 *
 * The website does not use these tables yet. Run the seeders only on a separate database that was
 * migrated with database/migrations/foundation, for example:
 *
 *   DB_DATABASE=database/foundation.sqlite php artisan migrate --path=database/migrations/foundation
 *   DB_DATABASE=database/foundation.sqlite php artisan db:seed --class="Database\Seeders\Foundation\FoundationSeeder"
 *
 * Every seeder can run more than once without creating duplicates.
 */
class FoundationSeeder extends Seeder
{
    public const MISSING_SCHEMA_MESSAGE = 'Skipped: this database does not have the foundation tables. Migrate a separate database with --path=database/migrations/foundation first.';

    public function run(): void
    {
        if (! self::schemaIsReady()) {
            $this->command?->error(self::MISSING_SCHEMA_MESSAGE);

            return;
        }

        $this->call([
            TicketPriceSeeder::class,
            AdminSeeder::class,
            SampleRegistrationSeeder::class,
        ]);
    }

    /**
     * Whether the current database has the foundation tables. The website's current database also
     * has users, admins and registrations tables in a different shape, so the seeders refuse to run there.
     */
    public static function schemaIsReady(): bool
    {
        return Schema::hasTable('ticket_prices') && Schema::hasColumn('registrations', 'registration_code');
    }
}
