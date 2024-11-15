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
        Schema::create('courses_blocks_learning_results_califications', function (Blueprint $table) {
            $table->uuid("uid");
            $table->uuid("user_uid");
            $table->uuid("course_block_uid");
            $table->uuid("learning_result_uid");
            $table->uuid("competence_framework_level_uid")->nullable();
            $table->string("calification_info")->nullable();

            $table->timestamps();

            $table->foreign("user_uid")->references("uid")->on("users")->cascadeOnDelete();
            $table->foreign("course_block_uid")->references("uid")->on("course_blocks")->cascadeOnDelete();
            $table->foreign("learning_result_uid")->references("uid")->on("learning_results")->cascadeOnDelete();
            $table->foreign("competence_framework_level_uid")->references("uid")->on("competence_frameworks_levels")->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses_blocks_learning_results_califications');
    }
};
