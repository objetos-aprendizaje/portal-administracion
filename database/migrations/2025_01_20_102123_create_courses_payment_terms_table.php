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
        Schema::create('courses_payment_terms', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->uuid('course_uid');
            $table->string('name');
            $table->timestamp('start_date');
            $table->timestamp('finish_date');
            $table->decimal('cost', 10);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses_payment_terms');
    }
};
