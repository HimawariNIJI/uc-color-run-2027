<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BCA QRIS payment attempts; a registration can have several (for example after one expires).
     * QRIS is the only method and payments are non-refundable, so there is no method or refund
     * column. status only becomes "success" from a confirmed gateway result, never from the
     * participant pressing the pay button. Payments are kept, so their registration cannot be deleted.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->index()->constrained()->restrictOnDelete();
            $table->string('payment_reference', 50)->unique();
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('pending')->index();
            $table->text('qr_code_url')->nullable();
            $table->text('qr_payload')->nullable();
            $table->string('gateway_reference', 100)->nullable()->unique();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
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
