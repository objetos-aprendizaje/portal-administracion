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
            $table->uuid("uid")->primary();
            $table->uuid('course_uid', 36);
            $table->foreign('course_uid')->references('uid')->on('courses')->cascadeOnDelete();
            $table->string('name');
            $table->dateTime('start_date');
            $table->dateTime('finish_date');
            $table->decimal('cost', 10, 2);
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
