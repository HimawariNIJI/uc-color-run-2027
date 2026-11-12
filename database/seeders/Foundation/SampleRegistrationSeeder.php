<?php

namespace Database\Seeders\Foundation;

use App\Support\BibNumber;
use Closure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Local-only sample data that covers every status in the schema: confirmed, pending, expired and
 * cancelled registrations, individual and bundling packages, a payment retry and racepack scans.
 *
 * Everyone is fictional: emails end in @sample.uccolorrun.test, phone numbers start with 0800,
 * identity numbers are zeros plus a counter and addresses are "Jl. Contoh". Only participants of
 * confirmed (paid) registrations receive a BIB number and a racepack QR token.
 * Re-running updates the same records instead of adding new ones.
 */
class SampleRegistrationSeeder extends Seeder
{
    public const EMAIL_DOMAIN = 'sample.uccolorrun.test';

    /**
     * PLACEHOLDER: minutes a QRIS code stays valid, the same as the website's current payment window.
     */
    private const QR_VALID_MINUTES = 60;

    private int $personNumber = 0;

    public function run(): void
    {
        if (! FoundationSeeder::schemaIsReady()) {
            $this->command?->error(FoundationSeeder::MISSING_SCHEMA_MESSAGE);

            return;
        }

        if (! app()->isLocal()) {
            $this->command?->warn('Skipped SampleRegistrationSeeder: it only runs in the local environment.');

            return;
        }

        $adminId = DB::table('admins')->where('username', AdminSeeder::USERNAME)->value('id')
            ?? throw new RuntimeException('Run AdminSeeder first: the sample racepack scans need an admin.');

        // A registration records what was paid, so samples are only made for packages with an
        // official price. While prices are still to be announced, no fake amounts are stored.
        $samples = array_filter(self::samples(), fn (array $sample): bool => DB::table('ticket_prices')
            ->where(Arr::only($sample, ['registration_type', 'bundle_type', 'race_category', 'price_type']))
            ->whereNotNull('price')
            ->exists());

        if ($samples === []) {
            $this->command?->info('Skipped sample registrations: no package has an official price yet.');

            return;
        }

        DB::transaction(function () use ($adminId, $samples): void {
            foreach ($samples as $index => $sample) {
                $this->seedRegistration($index + 1, $sample, $adminId);
            }

            $this->syncTicketPriceCounters();
        });

        $this->command?->info('Seeded '.count($samples).' of '.count(self::samples()).' sample registrations (@'.self::EMAIL_DOMAIN.'); the rest wait for official prices.');
    }

    /**
     * One entry per account. The first member is the account holder. "sequence" is the number
     * reserved in the registration code; the second sample is the example from the BIB brief
     * (10K, Early Bid, External, number 19). Payments are listed in the order they were attempted,
     * and scans are keyed by member position.
     *
     * @return list<array{registration_type: string, bundle_type: ?string, race_category: string, price_type: ?string, sequence: int, status: string, registered_days_ago: int, members: list<array{string, string, string, string, string}>, payments: list<string>, scans: array<int, list<string>>}>
     */
    private static function samples(): array
    {
        return [
            [
                'registration_type' => 'internal', 'bundle_type' => null, 'race_category' => '3k', 'price_type' => 'early_bird',
                'sequence' => 1, 'status' => 'confirmed', 'registered_days_ago' => 40,
                'members' => [['Andi Pratama', 'male', '2003-04-12', 'L', 'ktp']],
                'payments' => ['success'],
                'scans' => [0 => ['success', 'already_collected']],
            ],
            [
                'registration_type' => 'external', 'bundle_type' => null, 'race_category' => '10k', 'price_type' => 'early_bird',
                'sequence' => 19, 'status' => 'confirmed', 'registered_days_ago' => 32,
                'members' => [['Budi Santoso', 'male', '1990-09-03', 'XL', 'passport']],
                'payments' => ['expired', 'success'],
                'scans' => [],
            ],
            [
                'registration_type' => 'internal', 'bundle_type' => null, 'race_category' => '5k', 'price_type' => 'normal',
                'sequence' => 1, 'status' => 'pending_payment', 'registered_days_ago' => 2,
                'members' => [['Citra Lestari', 'female', '2004-01-25', 'S', 'ktp']],
                'payments' => ['pending'],
                'scans' => [0 => ['invalid']],
            ],
            [
                'registration_type' => 'external', 'bundle_type' => null, 'race_category' => '5k', 'price_type' => 'early_bird',
                'sequence' => 1, 'status' => 'expired', 'registered_days_ago' => 20,
                'members' => [['Dewi Anggraini', 'female', '1995-06-17', 'M', 'ktp']],
                'payments' => ['expired'],
                'scans' => [],
            ],
            [
                'registration_type' => 'external', 'bundle_type' => null, 'race_category' => '3k', 'price_type' => 'normal',
                'sequence' => 1, 'status' => 'cancelled', 'registered_days_ago' => 15,
                'members' => [['Eko Prasetyo', 'male', '1988-11-30', 'L', 'ktp']],
                'payments' => ['failed'],
                'scans' => [],
            ],
            [
                'registration_type' => 'bundling', 'bundle_type' => 'couple', 'race_category' => '5k', 'price_type' => null,
                'sequence' => 1, 'status' => 'confirmed', 'registered_days_ago' => 28,
                'members' => [
                    ['Fajar Nugroho', 'male', '1996-02-08', 'L', 'ktp'],
                    ['Gita Permata', 'female', '1997-07-21', 'S', 'ktp'],
                ],
                'payments' => ['success'],
                'scans' => [0 => ['success']],
            ],
            [
                'registration_type' => 'bundling', 'bundle_type' => 'family', 'race_category' => '3k', 'price_type' => null,
                'sequence' => 1, 'status' => 'pending_payment', 'registered_days_ago' => 1,
                'members' => [
                    ['Hendra Wijaya', 'male', '1979-05-14', 'XL', 'ktp'],
                    ['Indah Wijaya', 'female', '1981-10-02', 'M', 'ktp'],
                    ['Joko Wijaya', 'male', '2004-03-19', 'M', 'ktp'],
                    ['Kartika Wijaya', 'female', '2006-08-27', 'S', 'ktp'],
                ],
                'payments' => ['pending'],
                'scans' => [],
            ],
        ];
    }

    /**
     * @param  array{registration_type: string, bundle_type: ?string, race_category: string, price_type: ?string, sequence: int, status: string, registered_days_ago: int, members: list<array{string, string, string, string, string}>, payments: list<string>, scans: array<int, list<string>>}  $sample
     */
    private function seedRegistration(int $accountNumber, array $sample, int $adminId): void
    {
        $holderName = $sample['members'][0][0];
        $email = Str::slug($holderName, '.').'@'.self::EMAIL_DOMAIN;
        $registeredAt = now()->subDays($sample['registered_days_ago'])->startOfHour();

        $userId = $this->put('users', ['email' => $email], [
            'google_id' => str_pad((string) $accountNumber, 21, '0', STR_PAD_LEFT),
            'name' => $holderName,
            'avatar' => null,
            'last_login_at' => $registeredAt,
            'created_at' => $registeredAt->copy()->subMinutes(10),
            'updated_at' => $registeredAt,
        ]);

        $ticketPrice = DB::table('ticket_prices')->where([
            'registration_type' => $sample['registration_type'],
            'bundle_type' => $sample['bundle_type'],
            'race_category' => $sample['race_category'],
            'price_type' => $sample['price_type'],
        ])->first() ?? throw new RuntimeException('Run TicketPriceSeeder first.');

        $quantity = $ticketPrice->price_basis === 'per_package' ? 1 : count($sample['members']);
        $subtotal = number_format((float) $ticketPrice->price * $quantity, 2, '.', '');

        $prefix = BibNumber::prefix($sample['race_category'], $sample['price_type'], $sample['registration_type'], $sample['bundle_type']);
        $registrationCode = BibNumber::participantId(BibNumber::make($prefix, $sample['sequence']));

        $payments = $this->paymentAttempts($sample['payments'], $registeredAt);
        $confirmedAt = $sample['status'] === 'confirmed' ? collect($payments)->firstWhere('status', 'success')['paid_at'] : null;

        $registrationId = $this->put('registrations', ['user_id' => $userId], [
            'registration_code' => $registrationCode,
            'registration_type' => $sample['registration_type'],
            'bundle_type' => $sample['bundle_type'],
            'status' => $sample['status'],
            'total_amount' => $subtotal,
            'registered_at' => $registeredAt,
            'confirmed_at' => $confirmedAt,
            'created_at' => $registeredAt,
            'updated_at' => $confirmedAt ?? $registeredAt,
        ]);

        $this->put('registration_items', ['registration_id' => $registrationId, 'ticket_price_id' => $ticketPrice->id], [
            'quantity' => $quantity,
            'unit_price' => $ticketPrice->price,
            'subtotal' => $subtotal,
            'created_at' => $registeredAt,
            'updated_at' => $registeredAt,
        ]);

        foreach ($payments as $attempt => $payment) {
            $reference = 'PAY-'.$registrationCode.'-'.($attempt + 1);

            $this->put('payments', ['payment_reference' => $reference], [
                'registration_id' => $registrationId,
                'amount' => $subtotal,
                'status' => $payment['status'],
                'qr_code_url' => null,
                'qr_payload' => 'SAMPLE-QRIS-'.$reference,
                'gateway_reference' => $payment['status'] === 'success' ? 'SAMPLE-BCA-'.$reference : null,
                'paid_at' => $payment['paid_at'],
                'expired_at' => $payment['created_at']->copy()->addMinutes(self::QR_VALID_MINUTES),
                'created_at' => $payment['created_at'],
                'updated_at' => $payment['paid_at'] ?? $payment['created_at'],
            ]);
        }

        $scannedAt = now()->subDay()->startOfHour();

        foreach ($sample['members'] as $position => [$name, $gender, $dateOfBirth, $shirtSize, $identityType]) {
            $person = ++$this->personNumber;
            $bibNumber = $sample['status'] === 'confirmed' ? BibNumber::make($prefix, $sample['sequence'] + $position) : null;
            $scans = $sample['scans'][$position] ?? [];
            $successScan = array_search('success', $scans, true);
            $collectedAt = $successScan === false ? null : $scannedAt->copy()->addMinutes($successScan * 10);

            $participantId = $this->put(
                'participants',
                ['registration_id' => $registrationId, 'full_name' => $name],
                fn (bool $exists): array => [
                    'email' => $position === 0 ? $email : null,
                    'phone_number' => '0800'.str_pad((string) $person, 8, '0', STR_PAD_LEFT),
                    'date_of_birth' => $dateOfBirth,
                    'gender' => $gender,
                    'identity_type' => $identityType,
                    'identity_number' => Crypt::encryptString($identityType === 'ktp'
                        ? str_pad((string) $person, 16, '0', STR_PAD_LEFT)
                        : 'X'.str_pad((string) $person, 7, '0', STR_PAD_LEFT)),
                    'shirt_size' => $shirtSize,
                    'emergency_contact_name' => 'Keluarga '.Str::before($name, ' '),
                    'emergency_contact_phone' => '0800'.str_pad((string) (900 + $person), 8, '0', STR_PAD_LEFT),
                    'province' => 'Jawa Timur',
                    'city' => 'Surabaya',
                    'full_address' => "Jl. Contoh No. {$person}, Surabaya",
                    'race_category' => $sample['race_category'],
                    'bib_number' => $bibNumber,
                    'racepack_collected_at' => $collectedAt,
                    'created_at' => $registeredAt,
                    'updated_at' => $collectedAt ?? $confirmedAt ?? $registeredAt,
                ] + match (true) {
                    $bibNumber === null => ['racepack_qr_token' => null],
                    ! $exists => ['racepack_qr_token' => Str::random(40)],
                    default => [],
                },
            );

            foreach ($scans as $order => $status) {
                $this->put('racepack_scans', ['participant_id' => $participantId, 'status' => $status], [
                    'admin_id' => $adminId,
                    'scanned_at' => $scannedAt->copy()->addMinutes($order * 10),
                    'created_at' => $scannedAt->copy()->addMinutes($order * 10),
                    'updated_at' => $scannedAt->copy()->addMinutes($order * 10),
                ]);
            }
        }
    }

    /**
     * Times for each payment attempt. A pending attempt is recent so its QR code is still valid.
     *
     * @param  list<string>  $statuses
     * @return list<array{status: string, created_at: Carbon, paid_at: ?Carbon}>
     */
    private function paymentAttempts(array $statuses, Carbon $registeredAt): array
    {
        return collect($statuses)->map(function (string $status, int $attempt) use ($registeredAt): array {
            $createdAt = $status === 'pending'
                ? now()->subMinutes(10)->startOfMinute()
                : $registeredAt->copy()->addMinutes(5 + $attempt * 180);

            return [
                'status' => $status,
                'created_at' => $createdAt,
                'paid_at' => $status === 'success' ? $createdAt->copy()->addMinutes(3) : null,
            ];
        })->all();
    }

    /**
     * Recount every ticket price from the registrations in the database: confirmed registrations
     * are sold, registrations waiting for payment hold a reservation.
     */
    private function syncTicketPriceCounters(): void
    {
        $totals = DB::table('registration_items')
            ->join('registrations', 'registrations.id', '=', 'registration_items.registration_id')
            ->whereIn('registrations.status', ['confirmed', 'pending_payment'])
            ->groupBy('registration_items.ticket_price_id', 'registrations.status')
            ->selectRaw('registration_items.ticket_price_id, registrations.status, sum(registration_items.quantity) as quantity')
            ->get();

        DB::table('ticket_prices')->update(['sold_quantity' => 0, 'reserved_quantity' => 0]);

        foreach ($totals as $total) {
            DB::table('ticket_prices')->where('id', $total->ticket_price_id)->update([
                $total->status === 'confirmed' ? 'sold_quantity' : 'reserved_quantity' => (int) $total->quantity,
            ]);
        }
    }

    /**
     * Insert the row, or update the row matching $keys, and return its ID.
     *
     * @param  array<string, mixed>  $keys
     * @param  array<string, mixed>|Closure(bool): array<string, mixed>  $values
     */
    private function put(string $table, array $keys, array|Closure $values): int
    {
        DB::table($table)->updateOrInsert($keys, $values);

        return (int) DB::table($table)->where($keys)->value('id');
    }
}
