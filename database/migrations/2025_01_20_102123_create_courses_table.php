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
            $table->uuid('uid')->primary();
            $table->string('title');
            $table->text('image_path')->nullable();
            $table->uuid('call_uid')->nullable()->index('qvkei_call_uid');
            $table->uuid('course_status_uid')->index('qvkei_course_status_uid');
            $table->uuid('course_type_uid')->index('qvkei_course_type_uid');
            $table->string('status_reason', 1000)->nullable();
            $table->boolean('validate_student_registrations')->nullable();
            $table->text('presentation_video_url')->nullable();
            $table->text('description')->nullable();
            $table->string('objectives', 1000)->nullable();
            $table->integer('min_required_students')->nullable();
            $table->integer('ects_workload')->nullable();
            $table->decimal('cost')->nullable();
            $table->text('lms_url')->nullable();
            $table->uuid('related_course_editions_uid')->nullable();
            $table->timestamps();
            $table->boolean('featured_big_carrousel')->nullable();
            $table->string('featured_big_carrousel_title')->nullable();
            $table->string('featured_big_carrousel_description', 1000)->nullable();
            $table->string('featured_big_carrousel_image_path')->nullable();
            $table->boolean('featured_small_carrousel')->nullable();
            $table->timestamp('inscription_start_date')->nullable();
            $table->timestamp('inscription_finish_date')->nullable();
            $table->timestamp('realization_start_date')->nullable();
            $table->timestamp('realization_finish_date')->nullable();
            $table->string('evaluation_criteria', 1000)->nullable();
            $table->uuid('educational_program_uid')->nullable()->index('qvkei_courses_educational_program_uid_foreign');
            $table->uuid('creator_user_uid')->nullable()->index('qvkei_courses_creator_user_uid_foreign');
            $table->uuid('course_origin_uid')->nullable()->index('qvkei_courses_course_origin_uid_foreign');
            $table->uuid('center_uid')->nullable()->index('qvkei_courses_center_uid_foreign');
            $table->enum('calification_type', ['NUMERICAL', 'TEXTUAL'])->default('NUMERICAL');
            $table->string('contact_information', 1000)->nullable();
            $table->timestamp('enrolling_start_date')->nullable();
            $table->timestamp('enrolling_finish_date')->nullable();
            $table->boolean('belongs_to_educational_program')->default(false);
            $table->uuid('course_lms_uid')->nullable();
            $table->uuid('lms_system_uid')->nullable();
            $table->string('featured_slider_color_font', 10)->nullable();
            $table->string('identifier');
            $table->enum('payment_mode', ['SINGLE_PAYMENT', 'INSTALLMENT_PAYMENT']);
            $table->boolean('featured_big_carrousel_approved')->default(false);
            $table->boolean('featured_small_carrousel_approved')->default(false);
            $table->uuid('certification_type_uid')->nullable();
            $table->string('course_lms_id')->nullable();
            $table->uuid('certidigital_credential_uid')->nullable();
            $table->uuid('certidigital_teacher_credential_uid')->nullable();
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
