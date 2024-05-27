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
        Schema::create('courses_students_documents', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('user_uid', 36);
            $table->string('course_document_uid', 36);
            $table->text('document_path');

            $table->foreign('user_uid')->references('uid')->on('users')->onDelete('cascade');
            $table->foreign('course_document_uid')->references('uid')->on('course_documents')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses_students_documents');
    }
};
