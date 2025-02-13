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
        Schema::table('educational_resources_learning_results', function (Blueprint $table) {
            $table->foreign(['educational_resource_uid'], 'edu_res_uid_fk')->references(['uid'])->on('educational_resources')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['learning_result_uid'], 'learn_res_uid_fk')->references(['uid'])->on('learning_results')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('educational_resources_learning_results', function (Blueprint $table) {
            $table->dropForeign('edu_res_uid_fk');
            $table->dropForeign('learn_res_uid_fk');
        });
    }
};
