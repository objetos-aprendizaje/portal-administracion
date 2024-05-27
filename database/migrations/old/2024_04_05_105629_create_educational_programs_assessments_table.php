<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('educational_programs_assessments', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('user_uid', 36);
            $table->string('educational_program_uid', 36);
            $table->integer('calification');

            $table->foreign('user_uid')->references('uid')->on('users');
            $table->foreign('educational_program_uid', 'edu_prog_uid_fk')->references('uid')->on('educational_programs');
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
