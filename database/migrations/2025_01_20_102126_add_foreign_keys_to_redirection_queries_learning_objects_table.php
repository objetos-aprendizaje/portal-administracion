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
        Schema::table('redirection_queries_learning_objects', function (Blueprint $table) {
            $table->foreign(['course_type_uid'])->references(['uid'])->on('course_types')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('redirection_queries_learning_objects', function (Blueprint $table) {
            $table->dropForeign('redirection_queries_learning_objects_course_type_uid_foreign');
        });
    }
};
