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
        Schema::table('educational_programs_email_contacts', function (Blueprint $table) {
            $table->foreign(['educational_program_uid'], 'edu_prog_em_uid_fk')->references(['uid'])->on('educational_programs')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('educational_programs_email_contacts', function (Blueprint $table) {
            $table->dropForeign('edu_prog_em_uid_fk');
        });
    }
};
