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
        Schema::table('courses_embeddings', function (Blueprint $table) {
            $table->foreign(['course_uid'], 'courses_embeddings_course_uid_fkey')->references(['uid'])->on('courses')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses_embeddings', function (Blueprint $table) {
            $table->dropForeign('courses_embeddings_course_uid_fkey');
        });
    }
};
