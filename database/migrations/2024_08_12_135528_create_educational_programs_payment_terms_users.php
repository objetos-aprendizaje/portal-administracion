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
        Schema::create('educational_programs_payment_terms_users', function (Blueprint $table) {
            $table->uuid("uid")->primary();
            $table->uuid('educational_program_payment_term_uid');
            $table->foreign('educational_program_payment_term_uid', 'euptu_edu_payment_term_uid_fk')
                ->references('uid')->on('educational_programs_payment_terms')->cascadeOnDelete();
            $table->uuid('user_uid', 36);
            $table->foreign('user_uid', 'euptu_user_uid_fk')
                ->references('uid')->on('users')->cascadeOnDelete();

            $table->string("order_number", 12);
            $table->dateTime('payment_date')->nullable();
            $table->text('info')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->unique('order_number', 'euptu_order_number_unique');
            $table->unique(['educational_program_payment_term_uid', 'user_uid'], 'euptu_course_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educational_programs_payment_terms_users');
    }
};
