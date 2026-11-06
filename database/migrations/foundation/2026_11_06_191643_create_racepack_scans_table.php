<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every racepack QR scan at the collection desk, kept as a history next to
     * participants.racepack_collected_at. Scans always belong to a known participant; "invalid"
     * means that participant cannot collect yet. Admins with scans cannot be deleted.
     */
    public function up(): void
    {
        Schema::create('racepack_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('admin_id')->index()->constrained()->restrictOnDelete();
            $table->timestamp('scanned_at')->useCurrent();
            $table->string('status', 20);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('racepack_scans');
    }
};
