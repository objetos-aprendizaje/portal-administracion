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
        Schema::create('management_module_configuration', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->boolean('course_approval_required');
            $table->boolean('resource_approval_required');
            $table->boolean('course_state_change_managers');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('management_module_configuration');
    }
};
