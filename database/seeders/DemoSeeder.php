<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Sample participants for previewing the admin console locally.
 *
 * Run with: php artisan db:seed --class=DemoSeeder
 * Re-running replaces the previous sample data. Refuses to run outside the local environment.
 */
class DemoSeeder extends Seeder
{
    public const EMAIL_DOMAIN = 'demo.uccolorrun.test';

    private const ACCOUNTS = 64;

    private const REGISTRATIONS = 48;

    public function run(): void
    {
        if (! app()->isLocal()) {
            $this->command?->error('DemoSeeder only runs in the local environment.');

            return;
        }

        User::query()->where('email', 'like', '%@'.self::EMAIL_DOMAIN)->delete();

        $admin = Admin::query()->firstOrCreate(['username' => 'admin'], ['name' => 'Administrator', 'password' => 'password']);

        $users = collect(range(1, self::ACCOUNTS))->map(function (int $number): User {
            $name = fake('id_ID')->name();
            $createdAt = now()->subMinutes(random_int(60, 60 * 24 * 50));

            $user = User::factory()->create([
                'name' => $name,
                'email' => Str::slug($name, '.').'.'.$number.'@'.self::EMAIL_DOMAIN,
                'google_id' => $number % 9 === 0 ? null : (string) fake()->unique()->numerify('10##############'),
            ]);

            $user->forceFill([
                'created_at' => $createdAt,
                'last_login_at' => fake()->dateTimeBetween($createdAt, 'now'),
            ])->saveQuietly();

            return $user;
        });

        $users->shuffle()->take(self::REGISTRATIONS)->each(function (User $user, int $index) use ($admin): void {
            // Weight sign-ups towards the last week so every trend range has something to show.
            $createdAt = $index % 5 < 2
                ? now()->subMinutes(random_int(10, 60 * 24 * 7))
                : now()->subMinutes(random_int(60 * 24 * 7, 60 * 24 * 45));

            $factory = Registration::factory()->for($user);

            $registration = ($index % 3 === 0 ? $factory->normalPrice() : $factory)->create([
                'full_name' => $user->name,
                'email' => $user->email,
            ]);
            $registration->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();

            $outcome = random_int(1, 100);

            if ($outcome <= 70) {
                $registration->markAsPaid();
                $registration->forceFill(['paid_at' => $createdAt->copy()->addMinutes(random_int(2, 50))])->saveQuietly();

                if (random_int(1, 100) <= 55) {
                    $registration->markBibAsCollected($admin);
                    $registration->forceFill(['bib_collected_at' => now()->subMinutes(random_int(5, 60 * 24 * 3))])->saveQuietly();
                }
            } elseif ($outcome <= 88) {
                $registration->openPaymentWindow();
            } else {
                $registration->markAsFailed();
            }
        });

        $this->command?->info('Created '.self::ACCOUNTS.' sample accounts and '.self::REGISTRATIONS.' registrations (@'.self::EMAIL_DOMAIN.').');
    }
}
