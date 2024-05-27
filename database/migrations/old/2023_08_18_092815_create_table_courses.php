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
            $table->string('title', 255);
            $table->text('image_path')->nullable();
            $table->string('educational_program_type_uid', 36)->nullable();
            $table->string('call_uid', 36)->nullable();
            $table->string('course_status_uid', 36);
            $table->string('course_type_uid', 36);
            $table->text('status_reason')->nullable();
            $table->boolean('validate_student_registrations');
            $table->text('presentation_video_url')->nullable();
            $table->text('description')->nullable();
            $table->text('objectives')->nullable();
            $table->datetime('start_date');
            $table->datetime('end_date');
            $table->unsignedInteger('min_required_students');
            $table->text('validation_information');
            $table->string('ects_workload', 100);
            $table->text('cost')->nullable();
            $table->text('center')->nullable();
            $table->text('lms_url')->nullable();
            $table->boolean('requires_approval');
            $table->string('related_course_editions_uid', 36)->nullable();
            $table->timestamps();
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
