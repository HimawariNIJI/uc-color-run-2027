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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique();
            $table->string('participant_name')->default('Nama Lengkap');
            $table->string('race_category')->default('5K');
            $table->string('tshirt_size')->default('M');
            $table->string('item_name')->default('UC Color Run 5K');
            $table->unsignedInteger('amount')->default(150000);
            $table->string('status')->default('PENDING'); // PENDING, PAID, EXPIRE, CANCEL, DENY, CHALLENGED
            $table->text('qr_url')->nullable();
            $table->text('qr_string')->nullable();
            $table->json('raw_midtrans_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
