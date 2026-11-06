<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One registration per account. It holds one participant, or a whole package for bundling.
     *
     * registration_code is "UCCR-" followed by the number reserved for the first participant
     * when the registration is created. That participant only receives the number as their
     * BIB once the payment is confirmed (see Database\Seeders\Foundation\BibNumber).
     *
     * Accounts with a registration cannot be deleted, so payment records are never lost.
     */
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->string('registration_code', 20)->unique();
            $table->string('registration_type', 20);
            $table->string('bundle_type', 20)->nullable();
            $table->string('status', 20)->default('pending_payment')->index();
            $table->decimal('total_amount', 12, 2);
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
