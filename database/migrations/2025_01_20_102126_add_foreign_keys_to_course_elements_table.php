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
        Schema::table('course_elements', function (Blueprint $table) {
            $table->foreign(['subblock_uid'])->references(['uid'])->on('course_subblocks')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_elements', function (Blueprint $table) {
            $table->dropForeign('course_elements_subblock_uid_foreign');
        });
    }
};
