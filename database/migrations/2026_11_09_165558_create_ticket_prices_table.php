<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The official registration packages. Prices are whole Rupiah (NULL = to be announced) and
     * a NULL quota means "not announced": such a package stays closed. Slots taken are counted
     * from the registrations themselves while the package row is locked, so there are no
     * sold/reserved counters that could drift from the real registrations.
     */
    public function up(): void
    {
        Schema::create('ticket_prices', function (Blueprint $table) {
            $table->id();
            $table->string('registration_type', 20);
            $table->string('bundle_type', 20)->nullable();
            $table->string('race_category', 5);
            $table->string('price_type', 20)->nullable();
            $table->string('price_basis', 20);
            $table->unsignedTinyInteger('max_participants')->default(1);
            $table->unsignedInteger('price')->nullable();
            $table->unsignedInteger('quota')->nullable();
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
