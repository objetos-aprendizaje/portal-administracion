<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure the vector extension is available
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        // Get the table prefix
        $prefix = DB::getTablePrefix();

        // Create the table with the desired column order
        DB::statement("
            CREATE TABLE {$prefix}courses_embeddings (
                course_uid UUID PRIMARY KEY REFERENCES {$prefix}courses(uid) ON DELETE CASCADE,
                embeddings vector(1536),
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get the table prefix
        $prefix = DB::getTablePrefix();

        DB::statement("DROP TABLE IF EXISTS {$prefix}courses_embeddings");
    }
};
