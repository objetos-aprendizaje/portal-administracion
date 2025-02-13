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
            $table->uuid('educational_program_uid')->index('ep_students_ep_uid_idx');
            $table->uuid('user_uid')->index('ep_students_user_uid_idx');
            $table->timestamps();
            $table->string('credential')->nullable();
            $table->enum('status', ['INSCRIBED', 'ENROLLED'])->default('INSCRIBED');
            $table->enum('acceptance_status', ['PENDING', 'ACCEPTED', 'REJECTED']);
            $table->string('emissions_block_id')->nullable();
            $table->string('emissions_block_uuid')->nullable();
            $table->boolean('credential_sent')->default(false);
            $table->boolean('credential_sealed')->default(false);

            $table->unique(['educational_program_uid', 'user_uid'], 'ep_user_unique_educational_programs_students');
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
