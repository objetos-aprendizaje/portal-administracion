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
        Schema::create('course_documents', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->uuid('course_uid')->index('qvkei_course_documents_course_uid_foreign');
            $table->string('document_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_documents');
    }
};
