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
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('transaction_id', 50)->nullable()->unique()->after('paid_at');
            $table->timestamp('bib_collected_at')->nullable()->after('bib_number')->index();
            $table->foreignId('bib_collected_by')->nullable()->after('bib_collected_at')->constrained('admins')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bib_collected_by');
            $table->dropIndex(['bib_collected_at']);
            $table->dropColumn('bib_collected_at');
            $table->dropUnique(['transaction_id']);
            $table->dropColumn('transaction_id');
        });
    }
};
