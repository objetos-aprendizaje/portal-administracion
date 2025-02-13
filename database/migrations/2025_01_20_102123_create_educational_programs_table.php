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
        Schema::create('educational_programs', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->uuid('educational_program_type_uid')->index('qvkei_ep_educational_program_type_uid');
            $table->uuid('call_uid')->nullable();
            $table->string('keywords')->nullable();
            $table->timestamps();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('inscription_start_date')->nullable();
            $table->timestamp('inscription_finish_date')->nullable();
            $table->text('image_path')->nullable();
            $table->timestamp('enrolling_start_date')->nullable();
            $table->timestamp('enrolling_finish_date')->nullable();
            $table->integer('min_required_students')->nullable();
            $table->boolean('validate_student_registrations')->default(false);
            $table->text('evaluation_criteria')->nullable();
            $table->decimal('cost')->nullable();
            $table->boolean('featured_slider')->default(false);
            $table->string('featured_slider_title')->nullable();
            $table->text('featured_slider_description')->nullable();
            $table->string('featured_slider_color_font')->nullable();
            $table->string('featured_slider_image_path')->nullable();
            $table->boolean('featured_main_carrousel')->default(false);
            $table->uuid('educational_program_status_uid')->nullable();
            $table->string('status_reason', 1000)->nullable();
            $table->timestamp('realization_start_date')->nullable();
            $table->timestamp('realization_finish_date')->nullable();
            $table->uuid('educational_program_origin_uid')->nullable();
            $table->uuid('creator_user_uid')->nullable();
            $table->string('identifier')->nullable();
            $table->enum('payment_mode', ['SINGLE_PAYMENT', 'INSTALLMENT_PAYMENT'])->default('SINGLE_PAYMENT');
            $table->boolean('featured_slider_approved')->default(false);
            $table->boolean('featured_main_carrousel_approved')->default(false);
            $table->uuid('certidigital_credential_uid')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educational_programs');
    }
};
