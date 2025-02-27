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
        Schema::create('courses_emails_contacts', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->uuid('course_uid')->index('qvkei_courses_emails_contacts_course_uid_foreign');
            $table->string('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses_emails_contacts');
    }
};
