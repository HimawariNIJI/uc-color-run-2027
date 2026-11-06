<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every registration option and its price. There are no sale dates: an option is available
     * while is_active is true and sold_quantity + reserved_quantity is below quota.
     * reserved_quantity counts registrations that are waiting for payment.
     *
     * Quantities follow price_basis: per_person options count people, per_package options
     * count packages. bundle_type is NULL for non-bundling options, and databases treat NULLs
     * as distinct, so the unique index only fully guards bundling rows.
     */
    public function up(): void
    {
        Schema::create('ticket_prices', function (Blueprint $table) {
            $table->id();
            $table->string('registration_type', 20);
            $table->string('bundle_type', 20)->nullable();
            $table->string('race_category', 5);
            $table->string('price_type', 20);
            $table->string('price_basis', 20);
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('quota');
            $table->unsignedInteger('sold_quantity')->default(0);
            $table->unsignedInteger('reserved_quantity')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['registration_type', 'bundle_type', 'race_category', 'price_type'], 'ticket_prices_option_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_prices');
    }
};
