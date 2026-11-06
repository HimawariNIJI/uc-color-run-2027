<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The people who run. bib_number and racepack_qr_token stay empty until the payment is
     * confirmed. identity_number is encrypted by the application, so it is stored as text.
     */
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->index()->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone_number', 20);
            $table->date('date_of_birth');
            $table->string('gender', 10);
            $table->string('identity_type', 10);
            $table->text('identity_number');
            $table->string('shirt_size', 5);
            $table->string('emergency_contact_name');
            $table->string('emergency_contact_phone', 20);
            $table->string('province');
            $table->string('city');
            $table->text('full_address');
            $table->string('race_category', 5);
            $table->string('bib_number', 10)->nullable()->unique();
            $table->string('racepack_qr_token', 64)->nullable()->unique();
            $table->timestamp('racepack_collected_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
