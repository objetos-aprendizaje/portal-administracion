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
        Schema::table('issued_educational_credentials', function (Blueprint $table) {
            $table->foreign(['course_uid'])->references(['uid'])->on('courses')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['user_uid'])->references(['uid'])->on('users')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issued_educational_credentials', function (Blueprint $table) {
            $table->dropForeign('qvkei_issued_educational_credentials_course_uid_foreign');
            $table->dropForeign('qvkei_issued_educational_credentials_user_uid_foreign');
        });
    }
};
