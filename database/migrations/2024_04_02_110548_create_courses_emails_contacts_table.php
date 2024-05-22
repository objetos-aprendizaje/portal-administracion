<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCoursesEmailsContactsTable extends Migration
{
    public function up()
    {
        Schema::create('courses_emails_contacts', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('course_uid', 36);
            $table->string('email', 255);
            $table->foreign('course_uid')->references('uid')->on('courses');
        });
    }

    public function down()
    {
        Schema::dropIfExists('courses_emails_contacts');
    }
}
