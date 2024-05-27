<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoursesStudentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('courses_students', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('course_uid', 36);
            $table->string('user_uid', 36);
            $table->enum('calification_type', ['NUMERIC', 'TEXTUAL'])->nullable();
            $table->string('calification', 255)->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('course_uid')->references('uid')->on('courses')->onDelete('cascade');
            $table->foreign('user_uid')->references('uid')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('courses_students');
    }
}
