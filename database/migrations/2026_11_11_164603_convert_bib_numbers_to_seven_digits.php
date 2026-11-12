<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The 10-digit BIB prefixes (race category, price type, participant type) and the official
     * 7-digit format's 3-digit prefix for the same package. Each old prefix maps to exactly one new
     * one, so converting keeps every number unique.
     *
     * @var array<string, string>
     */
    private const PREFIXES = [
        '030101' => '031', '030102' => '032', '030201' => '033', '030202' => '034', '030004' => '035',
        '050101' => '051', '050102' => '052', '050201' => '053', '050202' => '054', '050003' => '055',
        '100101' => '101', '100102' => '102', '100201' => '103', '100202' => '104',
    ];

    private const CODE_PREFIX = 'UCCR-';

    /**
     * Switch BIBs to the official 7-digit format: a 3-digit package prefix and a 4-digit sequence.
     *
     *  - bib_sequences keeps the last sequence handed out per prefix, so numbers are reserved with a
     *    row lock and never reused (see App\Support\BibNumber::reserve).
     *  - reserved_bib_number keeps each participant's number between registering and paying; bundle
     *    members get theirs at registration too, so a bundle's numbers are consecutive.
     *  - Existing codes and BIBs in the 10-digit format are rewritten with the new prefix and the same
     *    sequence: UCCR-0301020001 / 0301020001 (3K Early Bird External, number 1) become
     *    UCCR-0320001 / 0320001. Midtrans order IDs (payments.order_id, registrations.transaction_id)
     *    keep the old code, because Midtrans knows the payments by those IDs.
     *
     * Nothing is changed when a 10-digit number has an unknown prefix; the migration stops first.
     * Other formats from before the 10-digit one are left as they are.
     */
    public function up(): void
    {
        $registrations = DB::table('registrations')->get(['id', 'code', 'bib_number']);
        $members = DB::table('registration_members')->get(['id', 'bib_number']);

        // Converting throws on an unknown prefix, so try every number before changing anything.
        foreach ($registrations as $registration) {
            $this->convertCode($registration->code);
            $this->convertBib($registration->bib_number);
        }

        foreach ($members as $member) {
            $this->convertBib($member->bib_number);
        }

        Schema::create('bib_sequences', function (Blueprint $table) {
            $table->char('prefix', 3)->primary();
            $table->unsignedSmallInteger('last_sequence')->default(0);
            $table->timestamps();
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->string('reserved_bib_number', 7)->nullable()->unique()->after('code');
        });

        Schema::table('registration_members', function (Blueprint $table) {
            $table->string('reserved_bib_number', 7)->nullable()->unique()->after('position');
        });

        DB::transaction(function () use ($registrations, $members): void {
            foreach ($registrations as $registration) {
                $code = $this->convertCode($registration->code);

                DB::table('registrations')->where('id', $registration->id)->update([
                    'code' => $code ?? $registration->code,
                    'reserved_bib_number' => $code === null ? null : substr($code, strlen(self::CODE_PREFIX)),
                    'bib_number' => $this->convertBib($registration->bib_number) ?? $registration->bib_number,
                ]);
            }

            foreach ($members as $member) {
                DB::table('registration_members')->where('id', $member->id)->update([
                    'bib_number' => $this->convertBib($member->bib_number) ?? $member->bib_number,
                ]);
            }

            $now = now();

            DB::table('bib_sequences')->insert(array_map(fn (string $prefix): array => [
                'prefix' => $prefix,
                'last_sequence' => $this->highestSequenceUsed($prefix),
                'created_at' => $now,
                'updated_at' => $now,
            ], array_values(self::PREFIXES)));
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->string('bib_number', 7)->nullable()->change();
        });

        Schema::table('registration_members', function (Blueprint $table) {
            $table->string('bib_number', 7)->nullable()->change();
        });
    }

    /**
     * Back to the 10-digit format, with the same sequences.
     */
    public function down(): void
    {
        $oldPrefixes = array_flip(self::PREFIXES);
        $toOld = fn (?string $number): ?string => $number !== null && preg_match('/^\d{7}$/', $number) === 1 && isset($oldPrefixes[substr($number, 0, 3)])
            ? $oldPrefixes[substr($number, 0, 3)].substr($number, 3)
            : $number;

        Schema::table('registrations', function (Blueprint $table) {
            $table->string('bib_number')->nullable()->change();
        });

        Schema::table('registration_members', function (Blueprint $table) {
            $table->string('bib_number', 10)->nullable()->change();
        });

        DB::transaction(function () use ($toOld): void {
            foreach (DB::table('registrations')->get(['id', 'code', 'bib_number']) as $registration) {
                $code = str_starts_with($registration->code, self::CODE_PREFIX)
                    ? self::CODE_PREFIX.$toOld(substr($registration->code, strlen(self::CODE_PREFIX)))
                    : $registration->code;

                DB::table('registrations')->where('id', $registration->id)->update(['code' => $code, 'bib_number' => $toOld($registration->bib_number)]);
            }

            foreach (DB::table('registration_members')->get(['id', 'bib_number']) as $member) {
                DB::table('registration_members')->where('id', $member->id)->update(['bib_number' => $toOld($member->bib_number)]);
            }
        });

        Schema::table('registration_members', function (Blueprint $table) {
            $table->dropUnique(['reserved_bib_number']);
            $table->dropColumn('reserved_bib_number');
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->dropUnique(['reserved_bib_number']);
            $table->dropColumn('reserved_bib_number');
        });

        Schema::dropIfExists('bib_sequences');
    }

    /**
     * The 7-digit version of a 10-digit BIB, or null for any other format.
     */
    private function convertBib(?string $bib): ?string
    {
        if ($bib === null || preg_match('/^\d{10}$/', $bib) !== 1) {
            return null;
        }

        $prefix = self::PREFIXES[substr($bib, 0, 6)] ?? null;

        if ($prefix === null) {
            throw new RuntimeException("BIB {$bib} has a prefix that is not an official package, so it cannot be converted. Nothing was changed.");
        }

        return $prefix.substr($bib, 6);
    }

    /**
     * The new code for a 10-digit registration code ("UCCR-0301020001"), or null for any other format.
     */
    private function convertCode(string $code): ?string
    {
        if (! str_starts_with($code, self::CODE_PREFIX)) {
            return null;
        }

        $number = $this->convertBib(substr($code, strlen(self::CODE_PREFIX)));

        return $number === null ? null : self::CODE_PREFIX.$number;
    }

    /**
     * The highest sequence already used for the prefix, in codes, reserved numbers and BIBs.
     */
    private function highestSequenceUsed(string $prefix): int
    {
        $numbers = collect([
            DB::table('registrations')->where('reserved_bib_number', 'like', $prefix.'%')->max('reserved_bib_number'),
            DB::table('registrations')->where('bib_number', 'like', $prefix.'%')->max('bib_number'),
            DB::table('registration_members')->where('reserved_bib_number', 'like', $prefix.'%')->max('reserved_bib_number'),
            DB::table('registration_members')->where('bib_number', 'like', $prefix.'%')->max('bib_number'),
        ]);

        return (int) $numbers
            ->filter(fn (?string $number): bool => $number !== null && preg_match('/^\d{7}$/', $number) === 1)
            ->map(fn (string $number): int => (int) substr($number, 3))
            ->max();
    }
};
