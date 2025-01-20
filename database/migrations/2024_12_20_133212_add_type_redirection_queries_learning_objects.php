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
        // Añadir tipo de objeto
        Schema::table('redirection_queries_learning_objects', function (Blueprint $table) {
            $table->enum('learning_object_type', ['COURSE', 'EDUCATIONAL_PROGRAM'])->default('COURSE');

            $table->uuid("course_type_uid")->nullable();
            $table->foreign('course_type_uid')->references('uid')->on('course_types');

            // hacer que el campo sea nullable
            $table->uuid("educational_program_type_uid")->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
