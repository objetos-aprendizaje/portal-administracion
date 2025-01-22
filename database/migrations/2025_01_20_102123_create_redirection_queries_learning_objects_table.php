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
        Schema::create('redirection_queries_learning_objects', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->uuid('educational_program_type_uid')->nullable();
            $table->enum('type', ['web', 'email']);
            $table->string('contact', 36);
            $table->timestamps();
            $table->enum('learning_object_type', ['COURSE', 'EDUCATIONAL_PROGRAM'])->default('COURSE');
            $table->uuid('course_type_uid')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redirection_queries_learning_objects');
    }
};
