<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link each registration to the package it bought. The price type becomes optional because
     * bundles have none, and Normal Bid is stored as "normal" like in the database PDF.
     * The amount column keeps what was charged, even if the package price changes later.
     */
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->foreignId('ticket_price_id')->nullable()->after('user_id')->constrained()->restrictOnDelete();
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->string('fee_period')->nullable()->change();
        });

        DB::table('registrations')->where('fee_period', 'regular')->update(['fee_period' => 'normal']);
    }

    /**
     * Rolling back only works while every registration still has a price type.
     */
    public function down(): void
    {
        DB::table('registrations')->where('fee_period', 'normal')->update(['fee_period' => 'regular']);

        Schema::table('registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ticket_price_id');
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->string('fee_period')->nullable(false)->change();
        });
    }
};
