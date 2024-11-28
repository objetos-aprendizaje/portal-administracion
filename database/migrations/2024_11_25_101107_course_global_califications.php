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

        // Crear tabla
        Schema::create('course_global_califications', function (Blueprint $table) {
            $table->uuid("uid")->primary();
            $table->uuid('course_uid');
            $table->uuid('user_uid');
            $table->text("calification_info");
            $table->timestamps();

            // Claves foráneas
            $table->foreign('course_uid')->references('uid')->on('courses');
            $table->foreign('user_uid')->references('uid')->on('users');

            // Restricicón para que no haya más de un registro por usuario y curso
            $table->unique(['course_uid', 'user_uid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
