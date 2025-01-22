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
        Schema::create('courses_visits', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->uuid('course_uid');
            $table->uuid('user_uid')->nullable();
            $table->timestamp('access_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses_visits');
    }
};
