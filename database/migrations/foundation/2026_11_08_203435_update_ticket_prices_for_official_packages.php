<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fit the official packages (8 November 2026):
     * - price can be NULL while it is "Price To Be Announced";
     * - quota can be NULL while it is not announced (never read as zero or unlimited);
     * - price_type can be NULL, because bundles have no Early Bid / Normal Bid;
     * - max_participants records the bundle capacity (Family 4, Couple 2; individual packages 1).
     */
    public function up(): void
    {
        Schema::table('ticket_prices', function (Blueprint $table) {
            $table->string('price_type', 20)->nullable()->change();
            $table->decimal('price', 12, 2)->nullable()->change();
            $table->unsignedInteger('quota')->nullable()->change();
        });

        Schema::table('ticket_prices', function (Blueprint $table) {
            $table->unsignedTinyInteger('max_participants')->default(1)->after('price_basis');
        });
    }

    /**
     * Rolling back only works while every package has a price, a quota and a price type again.
     */
    public function down(): void
    {
        Schema::table('ticket_prices', function (Blueprint $table) {
            $table->dropColumn('max_participants');
        });

        Schema::table('ticket_prices', function (Blueprint $table) {
            $table->string('price_type', 20)->nullable(false)->change();
            $table->decimal('price', 12, 2)->nullable(false)->change();
            $table->unsignedInteger('quota')->nullable(false)->change();
        });
    }
};
