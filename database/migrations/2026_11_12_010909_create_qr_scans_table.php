<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The racepack desk's scan history: one row per scan event. An accepted scan points at the
     * registration, so a bundle's scan stays linked to every participant through the registration's
     * members; the payload keeps the participant IDs that were scanned. Rejected scans are kept for
     * the audit trail only and never shown as successful. No participant details are copied here.
     *
     * Registrations whose BIB was already handed over get one accepted scan each (at the time and by
     * the admin of that hand-over), so Recent QR Scanned keeps showing them.
     */
    public function up(): void
    {
        Schema::create('qr_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('status', 10);
            $table->string('rejection_reason', 30)->nullable();
            $table->string('source', 10)->nullable();
            $table->string('payload')->nullable();
            $table->boolean('bib_handed_over')->default(false);
            $table->timestamp('scanned_at');
            $table->timestamps();

            $table->index(['status', 'scanned_at']);
            $table->index(['admin_id', 'scanned_at']);
        });

        $collected = DB::table('registrations')
            ->whereNotNull('bib_collected_at')
            ->orderBy('id')
            ->get(['id', 'bib_number', 'bib_collected_at', 'bib_collected_by']);

        foreach ($collected as $registration) {
            $memberBibs = DB::table('registration_members')->where('registration_id', $registration->id)->orderBy('position')->pluck('bib_number');
            $bibs = collect([$registration->bib_number, ...$memberBibs]);

            DB::table('qr_scans')->insert([
                'registration_id' => $registration->id,
                'admin_id' => $registration->bib_collected_by,
                'status' => 'accepted',
                'source' => null,
                'payload' => $bibs->contains(null) ? null : $bibs->map(fn (string $bib): string => 'UCCR-'.$bib)->implode(';'),
                'bib_handed_over' => true,
                'scanned_at' => $registration->bib_collected_at,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_scans');
    }
};
