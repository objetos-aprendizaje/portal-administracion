<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoursesStudentsDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('courses_students_documents', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('courses_students_uid', 36);
            $table->string('course_documents_uid', 36);
            $table->string('document_path', 255);

            // Foreign keys
            $table->foreign('courses_students_uid')->references('uid')->on('courses_students')->onDelete('cascade');
            $table->foreign('course_documents_uid')->references('uid')->on('course_documents')->onDelete('cascade');

            // Making sure all fields are not null
            $table->string('uid', 36)->nullable(false)->change();
            $table->string('courses_students_uid', 36)->nullable(false)->change();
            $table->string('course_documents_uid', 36)->nullable(false)->change();
            $table->string('document_path', 255)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('courses_students_documents');
    }
}
