<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The other people in a Family or Couple Bundle. The account holder stays on the registration
     * itself (position 1); members are positions 2 to 4 and share the holder's emergency contact
     * and address. Each member receives a BIB once the bundle is paid.
     */
    public function up(): void
    {
        Schema::create('registration_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->string('full_name');
            $table->string('phone', 20)->nullable();
            $table->date('birth_date');
            $table->string('gender', 10);
            $table->text('identity_number');
            $table->string('tshirt_size', 5);
            $table->string('bib_number', 10)->nullable()->unique();
            $table->timestamps();

            $table->unique(['registration_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registration_members');
    }
};
