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
        Schema::create('educational_programs_students_documents', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('user_uid', 36);
            $table->string('educational_program_uid', 36);
            $table->text('document_path');
            $table->timestamps();

            $table->foreign('user_uid', 'epsd_user_uid_foreign')->references('uid')->on('users')->onDelete('cascade');
            $table->foreign('educational_program_uid', 'epsd_ep_uid_foreign')->references('uid')->on('educational_programs_documents')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educational_programs_students_documents');
    }
};
