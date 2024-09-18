<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('competence_frameworks_levels', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('competence_framework_uid', 36);
            $table->string('name', 255);
            $table->string('origin_code', 255)->nullable();
            $table->timestamps();

            $table->foreign('competence_framework_uid', 'cf_uid_fk')
                ->references('uid')
                ->on('competence_frameworks')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competence_frameworks_levels');
    }
};
