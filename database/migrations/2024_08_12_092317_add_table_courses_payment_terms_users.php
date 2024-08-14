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
            $table->uuid("uid")->primary();
            $table->uuid('course_payment_term_uid');
            $table->foreign('course_payment_term_uid', 'cptu_course_payment_term_uid_fk')
                ->references('uid')->on('courses_payment_terms')->cascadeOnDelete();
            $table->uuid('user_uid');
            $table->foreign('user_uid', 'cptu_user_uid_fk')
                ->references('uid')->on('users')->cascadeOnDelete();
            $table->string("order_number", 12);
            $table->dateTime('payment_date')->nullable();
            $table->text('info')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->unique('order_number');
            $table->unique(['course_payment_term_uid', 'user_uid'], 'cptu_course_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
