<?php

namespace Database\Seeders\Foundation;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Local-only admin account for trying the admin console: username "admin", password "password".
 * Re-running keeps the existing password.
 */
class AdminSeeder extends Seeder
{
    public const USERNAME = 'admin';

    public function run(): void
    {
        if (! FoundationSeeder::schemaIsReady()) {
            $this->command?->error(FoundationSeeder::MISSING_SCHEMA_MESSAGE);

            return;
        }

        if (! app()->isLocal()) {
            $this->command?->warn('Skipped AdminSeeder: it only runs in the local environment.');

            return;
        }

        DB::table('admins')->updateOrInsert(
            ['username' => self::USERNAME],
            fn (bool $exists): array => $exists ? [] : [
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }
}
