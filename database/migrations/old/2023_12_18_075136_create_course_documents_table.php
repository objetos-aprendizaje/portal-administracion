<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCourseDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('course_documents', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('course_uid', 36);
            $table->string('document_name', 255);
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('course_uid')->references('uid')->on('courses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('course_documents');
    }
}
