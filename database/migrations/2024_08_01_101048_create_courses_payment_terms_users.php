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
            $table->uuid('course_payment_term_uid');
            $table->uuid('user_uid', 36);
            $table->dateTime('payment_date');

            $table->primary(['course_payment_term_uid', 'user_uid']);

            $table->foreign('course_payment_term_uid', 'cptu_foreign')
                ->references('uid')
                ->on('courses_payment_terms')
                ->cascadeOnDelete();

            $table->foreign('user_uid', 'uu_foreign')
                ->references('uid')
                ->on('users')
                ->cascadeOnDelete();
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
