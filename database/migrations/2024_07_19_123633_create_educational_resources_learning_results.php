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
        Schema::create('educational_resources_learning_results', function (Blueprint $table) {
            $table->string('educational_resource_uid', 36);
            $table->string('learning_result_uid', 36);

            $table->foreign('educational_resource_uid', 'edu_res_uid_fk')
                ->references('uid')->on('educational_resources')
                ->onDelete('cascade');

            $table->foreign('learning_result_uid', 'learn_res_uid_fk')
                ->references('uid')->on('learning_results')
                ->onDelete('cascade');

            $table->primary(['educational_resource_uid', 'learning_result_uid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educational_resources_learning_results');
    }
};
