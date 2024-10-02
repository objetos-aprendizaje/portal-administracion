<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateFieldLengthsInVariousTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Obtener el prefijo de las tablas
        $prefix = DB::getTablePrefix();

        // List of tables and columns to truncate
        $tables = [
            'footer_pages' => ['content'],
            'header_pages' => ['content'],
            'tooltip_texts' => ['description'],
            'competences' => ['description'],
            'learning_results' => ['description'],
            'courses' => ['status_reason', 'description', 'contact_information', 'objectives', 'evaluation_criteria', 'featured_big_carrousel_description'],
            'educational_programs' => ['status_reason', 'description'],
            'educational_resources' => ['description'],
            'calls' => ['description'],
            'email_notifications' => ['body'],
            'general_notifications' => ['description'],
            'course_blocks' => ['description'],
            'course_subblocks' => ['description'],
            'course_elements' => ['description'],
            'course_subelements' => ['description'],
            'categories' => ['description'],
            'certification_types' => ['description'],
            'course_types' => ['description'],
            'educational_program_types' => ['description'],
            'educational_resource_types' => ['description'],
            'notifications_types' => ['description'],
        ];

        // Truncate all fields that exceed 1000 characters
        foreach ($tables as $table => $columns) {
            $tableName = $prefix . $table;
            foreach ($columns as $column) {
                // Truncar los valores que exceden la longitud de 1000 caracteres
                DB::statement("UPDATE \"$tableName\" SET \"$column\" = LEFT(\"$column\", 1000) WHERE LENGTH(\"$column\") > 1000;");

                // Hacer que la columna sea nullable y cambiar su longitud
                DB::statement("ALTER TABLE \"$tableName\" ALTER COLUMN \"$column\" TYPE VARCHAR(1000), ALTER COLUMN \"$column\" DROP NOT NULL;");
            }
        }
    }
}
