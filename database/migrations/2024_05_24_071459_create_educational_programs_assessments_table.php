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
        Schema::create('educational_programs_assessments', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('user_uid', 36)->index('qvkei_educational_programs_assessments_user_uid_foreign');
            $table->string('educational_program_uid', 36)->index('edu_prog_uid_fk');
            $table->integer('calification');
            $table->timestamps();

            $table->unique(['user_uid', 'educational_program_uid'], 'unique_user_uid_educational_program_uid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educational_programs_assessments');
    }
};
