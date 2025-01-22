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
        Schema::table('courses_payment_terms_users', function (Blueprint $table) {
            $table->foreign(['user_uid'])->references(['uid'])->on('users')->onUpdate('no action')->onDelete('restrict');
            $table->foreign(['course_payment_term_uid'], 'cptu_course_payment_term_uid_fk')->references(['uid'])->on('courses_payment_terms')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses_payment_terms_users', function (Blueprint $table) {
            $table->dropForeign('courses_payment_terms_users_user_uid_foreign');
            $table->dropForeign('cptu_course_payment_term_uid_fk');
        });
    }
};
