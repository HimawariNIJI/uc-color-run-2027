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
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('type');
            $table->string('category');
            $table->string('full_name');
            $table->string('email');
            $table->string('phone', 20);
            $table->date('birth_date');
            $table->string('gender', 10);
            $table->text('identity_number');
            $table->string('tshirt_size', 5);
            $table->string('emergency_contact_name');
            $table->string('emergency_contact_phone', 20);
            $table->string('province');
            $table->string('city');
            $table->string('address');
            $table->unsignedInteger('amount');
            $table->string('fee_period');
            $table->string('payment_status')->default('unpaid')->index();
            $table->timestamp('payment_expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('bib_number')->nullable()->unique();
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
