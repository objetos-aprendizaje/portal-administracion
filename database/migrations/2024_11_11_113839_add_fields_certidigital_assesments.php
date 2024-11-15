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
        Schema::table('certidigital_assesments', function (Blueprint $table) {
            $table->uuid('course_block_uid', 36)->nullable();
            $table->uuid('learning_result_uid', 36)->nullable();
        });

        Schema::table('certidigital_assesments', function (Blueprint $table) {
            $table->foreign('course_block_uid')->references('uid')->on('course_blocks')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('learning_result_uid')->references('uid')->on('learning_results')->onDelete('set null')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
