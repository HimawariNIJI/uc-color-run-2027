<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat Penyakit and Alergi Obat for the payer and every bundle member, and a photo of the
     * payer's KTP instead of a typed KTP/Passport number. Bundle members keep their typed number,
     * because children in a Family Bundle have no KTP.
     *
     * Every new column is nullable: registrations made before these fields existed keep NULL
     * ("not provided") instead of made-up values. The old payer KTP/Passport numbers stay as they are.
     * Medical information is stored encrypted (see the model casts); the KTP photo itself lives on
     * the private "ktp" disk and only its path is stored here.
     */
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('ktp_photo_path')->nullable()->after('identity_number');
            $table->text('medical_history')->nullable()->after('emergency_contact_phone');
            $table->text('drug_allergies')->nullable()->after('medical_history');
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->text('identity_number')->nullable()->change();
        });

        Schema::table('registration_members', function (Blueprint $table) {
            $table->text('medical_history')->nullable()->after('tshirt_size');
            $table->text('drug_allergies')->nullable()->after('medical_history');
        });
    }

    /**
     * Rolling back only works while every registration still has a typed KTP/Passport number,
     * so it fails once registrations with a KTP photo instead exist.
     */
    public function down(): void
    {
        Schema::table('registration_members', function (Blueprint $table) {
            $table->dropColumn(['medical_history', 'drug_allergies']);
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->text('identity_number')->nullable(false)->change();
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn(['ktp_photo_path', 'medical_history', 'drug_allergies']);
        });
    }
};
