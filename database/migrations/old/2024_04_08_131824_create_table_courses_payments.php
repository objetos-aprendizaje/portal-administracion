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
        Schema::create('courses_payments', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('user_uid', 36);
            $table->string('course_uid', 36);
            $table->string('order_number', 12)->unique();
            $table->text('info')->nullable();
            $table->tinyInteger('is_paid');

            $table->timestamps();

            $table->foreign('user_uid')->references('uid')->on('users');
            $table->foreign('course_uid')->references('uid')->on('courses');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses_payments');
    }
};
