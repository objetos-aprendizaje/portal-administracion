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
        Schema::table('courses_emails_contacts', function (Blueprint $table) {
            $table->foreign(['course_uid'])->references(['uid'])->on('courses')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses_emails_contacts', function (Blueprint $table) {
            $table->dropForeign('courses_emails_contacts_course_uid_foreign');
        });
    }
};
