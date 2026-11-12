<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BIBs now follow the order in which payments are confirmed, so no number is reserved at
     * registration any more (see App\Support\BibNumber). The columns that kept those reserved
     * numbers are dropped. Issued BIBs (bib_number), registration codes, payments and the BIB
     * sequences are not touched, and sequences never go back, so no number is ever handed out twice.
     */
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropUnique(['reserved_bib_number']);
            $table->dropColumn('reserved_bib_number');
        });

        Schema::table('registration_members', function (Blueprint $table) {
            $table->dropUnique(['reserved_bib_number']);
            $table->dropColumn('reserved_bib_number');
        });
    }

    /**
     * Bring the columns back, empty: the numbers they held were never issued as BIBs.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('reserved_bib_number', 7)->nullable()->unique()->after('code');
        });

        Schema::table('registration_members', function (Blueprint $table) {
            $table->string('reserved_bib_number', 7)->nullable()->unique()->after('position');
        });
    }
};
