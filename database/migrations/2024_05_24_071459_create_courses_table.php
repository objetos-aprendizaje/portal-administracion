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
        Schema::create('courses', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('title');
            $table->text('image_path')->nullable();
            $table->string('educational_program_type_uid', 36)->nullable()->index('educational_program_type_uid');
            $table->string('call_uid', 36)->nullable()->index('call_uid');
            $table->string('course_status_uid', 36)->index('course_status_uid');
            $table->string('course_type_uid', 36)->index('course_type_uid');
            $table->text('status_reason')->nullable();
            $table->boolean('validate_student_registrations')->default(false);
            $table->text('presentation_video_url')->nullable();
            $table->text('description')->nullable();
            $table->text('objectives')->nullable();
            $table->unsignedInteger('min_required_students')->default(0);
            $table->string('ects_workload', 100);
            $table->text('cost')->nullable();
            $table->text('lms_url')->nullable();
            $table->string('related_course_editions_uid', 36)->nullable();
            $table->timestamps();
            $table->boolean('featured_big_carrousel')->default(false);
            $table->string('featured_big_carrousel_title')->nullable();
            $table->text('featured_big_carrousel_description')->nullable();
            $table->string('featured_big_carrousel_image_path')->nullable();
            $table->boolean('featured_small_carrousel')->default(false);
            $table->dateTime('inscription_start_date')->nullable();
            $table->dateTime('inscription_finish_date')->nullable();
            $table->dateTime('realization_start_date')->nullable();
            $table->dateTime('realization_finish_date')->nullable();
            $table->text('evaluation_criteria')->nullable();
            $table->string('educational_program_uid', 36)->nullable()->index('qvkei_courses_educational_program_uid_foreign');
            $table->string('creator_user_uid', 36)->nullable()->index('qvkei_courses_creator_user_uid_foreign');
            $table->string('course_origin_uid', 36)->nullable()->index('qvkei_courses_course_origin_uid_foreign');
            $table->string('center_uid', 36)->nullable()->index('qvkei_courses_center_uid_foreign');
            $table->enum('calification_type', ['NUMERICAL', 'TEXTUAL'])->default('NUMERICAL');
            $table->text('contact_information')->nullable();
            $table->dateTime('enrolling_start_date')->nullable();
            $table->dateTime('enrolling_finish_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
