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
        Schema::create('courses_students', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->uuid('course_uid')->index('qvkei_courses_students_course_uid_foreign');
            $table->uuid('user_uid')->index('qvkei_courses_students_user_uid_foreign');
            $table->timestamps();
            $table->string('credential')->nullable();
            $table->enum('status', ['INSCRIBED', 'ENROLLED'])->default('INSCRIBED');
            $table->enum('acceptance_status', ['PENDING', 'ACCEPTED', 'REJECTED'])->default('PENDING');
            $table->integer('emissions_block_id')->nullable();
            $table->string('emissions_block_uuid')->nullable();
            $table->boolean('credential_sent')->default(false);
            $table->boolean('credential_sealed')->default(false);

            $table->unique(['course_uid', 'user_uid'], 'unique_course_user_uid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses_students');
    }
};
