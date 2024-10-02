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
        Schema::create('educational_programs_students', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->uuid('educational_program_uid');
            $table->uuid('user_uid', 36);
            $table->enum('calification_type', ['NUMERIC', 'TEXTUAL'])->nullable();
            $table->string('calification')->nullable();
            $table->timestamps();
            $table->boolean('approved')->nullable();
            $table->string('credential')->nullable();
            $table->enum('status', ['INSCRIBED', 'ENROLLED'])->default('INSCRIBED');

            // Indexes
            $table->index('educational_program_uid', 'ep_students_ep_uid_idx');
            $table->index('user_uid', 'ep_students_user_uid_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educational_programs_students');
    }
};
