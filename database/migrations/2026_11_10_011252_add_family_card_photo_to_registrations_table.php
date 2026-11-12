<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A Family Bundle proves identity with a photo of the family's Kartu Keluarga instead of the
     * payer's KTP, and its members no longer type a KTP/Passport number. Couple Bundle members
     * still do. Existing rows keep their values; the new column stays NULL for them.
     */
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('family_card_photo_path')->nullable()->after('ktp_photo_path');
        });

        Schema::table('registration_members', function (Blueprint $table) {
            $table->text('identity_number')->nullable()->change();
        });
    }

    /**
     * Rolling back only works while every bundle member still has a typed KTP/Passport number,
     * so it fails once Family Bundles registered without them exist.
     */
    public function down(): void
    {
        Schema::table('registration_members', function (Blueprint $table) {
            $table->text('identity_number')->nullable(false)->change();
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn('family_card_photo_path');
        });
    }
};
