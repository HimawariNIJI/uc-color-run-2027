<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->string('registration_code')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // 1. Personal Information
            $table->string('full_name');
            $table->string('email');
            $table->string('phone_number');
            $table->date('date_of_birth');
            $table->string('gender'); // Male / Female
            $table->string('ktp_passport');

            // 2. Race Information
            $table->string('race_category'); // 5K Run, 10K Run, 21K Half Marathon
            $table->string('tshirt_size'); // XS, S, M, L, XL, XXL
            $table->string('emergency_name');
            $table->string('emergency_phone');

            // 3. Address Information
            $table->string('province');
            $table->string('city');
            $table->text('address');

            // Payment & BIB status
            $table->unsignedInteger('amount')->default(150000);
            $table->string('payment_status')->default('Pending'); // Unpaid, Pending, Paid
            $table->string('bib_number')->nullable();
            $table->string('order_id')->nullable()->index();
            $table->boolean('agreement')->default(true);

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
