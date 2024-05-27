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
        Schema::create('educational_resources_assessments', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('user_uid', 36);
            $table->string('educational_resources_uid', 36);
            $table->integer('calification');

            $table->foreign('user_uid')->references('uid')->on('users');
            $table->foreign('educational_resources_uid', 'edu_res_fk')->references('uid')->on('educational_resources');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educational_resources_assessments');
    }
};
