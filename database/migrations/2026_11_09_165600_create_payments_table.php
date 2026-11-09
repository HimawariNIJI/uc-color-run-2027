<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Midtrans QRIS payment attempts. A registration can have several (a new one after one expires),
     * but only one pending at a time. order_id is what Midtrans knows the attempt by; status only
     * becomes "success" from a verified Midtrans result. Payments are kept, so their registration
     * cannot be deleted.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->index()->constrained()->restrictOnDelete();
            $table->string('order_id', 50)->unique();
            $table->string('gateway_transaction_id', 100)->nullable()->unique();
            $table->unsignedInteger('amount');
            $table->string('status', 20)->default('pending')->index();
            $table->text('qr_string')->nullable();
            $table->text('qr_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
