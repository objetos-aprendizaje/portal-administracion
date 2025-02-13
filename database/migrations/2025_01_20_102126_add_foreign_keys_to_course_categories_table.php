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
        Schema::table('course_categories', function (Blueprint $table) {
            $table->foreign(['category_uid'])->references(['uid'])->on('categories')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['course_uid'])->references(['uid'])->on('courses')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_categories', function (Blueprint $table) {
            $table->dropForeign('course_categories_category_uid_foreign');
            $table->dropForeign('course_categories_course_uid_foreign');
        });
    }
};
