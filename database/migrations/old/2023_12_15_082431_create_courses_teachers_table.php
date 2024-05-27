<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoursesTeachersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('courses_teachers', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->uuid('course_uid');
            $table->uuid('user_uid');
            $table->timestamps();

            // Foreign keys
            $table->foreign('course_uid')->references('uid')->on('courses')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
            $table->foreign('user_uid')->references('uid')->on('users')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('courses_teachers');
    }
}
