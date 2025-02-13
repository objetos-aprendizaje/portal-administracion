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
        Schema::create('courses_payment_terms_users', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->uuid('course_payment_term_uid');
            $table->uuid('user_uid');
            $table->string('order_number', 12)->unique();
            $table->timestamp('payment_date')->nullable();
            $table->text('info')->nullable();
            $table->boolean('is_paid')->default(false);

            $table->unique(['course_payment_term_uid', 'user_uid'], 'cptu_course_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses_payment_terms_users');
    }
};
