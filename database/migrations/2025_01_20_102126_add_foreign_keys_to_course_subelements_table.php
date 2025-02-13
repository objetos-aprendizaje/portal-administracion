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
        Schema::table('course_subelements', function (Blueprint $table) {
            $table->foreign(['element_uid'])->references(['uid'])->on('course_elements')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_subelements', function (Blueprint $table) {
            $table->dropForeign('course_subelements_element_uid_foreign');
        });
    }
};
