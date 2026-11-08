<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            // Kode registrasi yang tampil di tabel "Recent QR Scanned" (mis. UCR-2601).
            $table->string('registration_id')->nullable()->unique()->after('id');

            // Terisi saat status sudah berhasil ditulis ke Google Sheets.
            $table->timestamp('sheets_synced_at')->nullable()->after('collected_at');

            $table->index('collected_at');
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropIndex(['collected_at']);
            $table->dropUnique(['registration_id']);
            $table->dropColumn(['registration_id', 'sheets_synced_at']);
        });
    }
};
