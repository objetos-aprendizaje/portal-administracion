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
        Schema::create('courses_teachers', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->uuid('course_uid')->index('qvkei_courses_teachers_course_uid_foreign');
            $table->uuid('user_uid')->index('qvkei_courses_teachers_user_uid_foreign');
            $table->timestamps();
            $table->string('credential')->nullable();
            $table->enum('type', ['COORDINATOR', 'NO_COORDINATOR'])->default('NO_COORDINATOR');
            $table->string('emissions_block_uuid')->nullable();
            $table->integer('emissions_block_id')->nullable();
            $table->boolean('credential_sent')->default(false);
            $table->boolean('credential_sealed')->default(false);

            $table->unique(['course_uid', 'user_uid'], 'qvkei_unique_course_user_uid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses_teachers');
    }
};
