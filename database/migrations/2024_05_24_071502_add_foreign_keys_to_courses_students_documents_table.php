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
        Schema::table('courses_students_documents', function (Blueprint $table) {
            $table->foreign(['course_document_uid'])->references(['uid'])->on('course_documents')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['user_uid'])->references(['uid'])->on('users')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses_students_documents', function (Blueprint $table) {
            $table->dropForeign('qvkei_courses_students_documents_course_document_uid_foreign');
            $table->dropForeign('qvkei_courses_students_documents_user_uid_foreign');
        });
    }
};
