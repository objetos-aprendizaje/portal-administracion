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
        Schema::table('course_subblocks', function (Blueprint $table) {
            $table->foreign(['block_uid'])->references(['uid'])->on('course_blocks')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_subblocks', function (Blueprint $table) {
            $table->dropForeign('course_subblocks_block_uid_foreign');
        });
    }
};
