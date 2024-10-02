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
            $table->uuid('uid', 36)->primary();
            $table->uuid('user_uid', 36)->index('qvkei_courses_payments_user_uid_foreign');
            $table->uuid('course_uid', 36)->index('qvkei_courses_payments_course_uid_foreign');
            $table->string('order_number', 12);
            $table->text('info')->nullable();
            $table->tinyInteger('is_paid');
            $table->timestamps();
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
