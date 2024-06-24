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
        Schema::create('educational_programs_documents', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('educational_program_uid', 36);
            $table->string('document_name', 255);
            $table->timestamps();

            $table->foreign('educational_program_uid', 'epd_ep_uid_foreign')->references('uid')->on('educational_programs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educational_programs_documents');
    }
};
