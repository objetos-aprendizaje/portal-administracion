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
        Schema::rename('redirection_queries_educational_program_types', 'redirection_queries_learning_objects');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
