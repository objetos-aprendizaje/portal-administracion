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
        Schema::table('courses_tags', function (Blueprint $table) {
            $table->foreign(['course_uid'], 'qvkei_courses_tags_ibfk_1')->references(['uid'])->on('courses')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses_tags', function (Blueprint $table) {
            $table->dropForeign('qvkei_courses_tags_ibfk_1');
        });
    }
};
