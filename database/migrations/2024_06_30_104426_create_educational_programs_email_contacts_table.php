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
        Schema::create('educational_programs_email_contacts', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('educational_program_uid', 36);
            $table->foreign('educational_program_uid', 'edu_prog_em_uid_fk') // Shortened name for the foreign key
                  ->references('uid')->on('educational_programs')
                  ->onDelete('cascade');
            $table->string('email', 255);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educational_programs_email_contacts');
    }
};
