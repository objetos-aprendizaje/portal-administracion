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
            $table->uuid('uid')->primary();
            $table->uuid('user_uid')->index('qvkei_courses_students_documents_user_uid_foreign');
            $table->uuid('course_document_uid')->index('qvkei_courses_students_documents_course_document_uid_foreign');
            $table->text('document_path');
            $table->timestamps();

            $table->unique(['course_document_uid', 'user_uid'], 'unique_course_document_uid_user_uid');
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
