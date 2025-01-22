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
        Schema::create('course_learning_result_califications', function (Blueprint $table) {
            $table->uuid('uid');
            $table->uuid('user_uid');
            $table->uuid('course_uid');
            $table->uuid('learning_result_uid');
            $table->uuid('competence_framework_level_uid')->nullable();
            $table->string('calification_info')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_learning_result_califications');
    }
};
