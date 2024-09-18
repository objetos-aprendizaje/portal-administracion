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
             'footer_pages' => 'content',
             'header_pages' => 'content',
             'tooltip_texts' => 'description',
             'competences' => 'description',
             'learning_results' => 'description',
             'courses' => ['status_reason', 'description', 'contact_information', 'objectives', 'evaluation_criteria', 'featured_big_carrousel_description'],
             'educational_programs' => ['status_reason', 'description'],
             'educational_resources' => 'description',
             'calls' => 'description',
             'email_notifications' => 'body',
             'general_notifications' => 'description',
             'course_blocks' => 'description',
             'course_subblocks' => 'description',
             'course_elements' => 'description',
             'course_subelements' => 'description'
         ];

         // Truncate all fields that exceed 1000 characters
         foreach ($tables as $table => $columns) {
             $tableName = $prefix . $table;
             if (is_array($columns)) {
                 foreach ($columns as $column) {
                     DB::statement("UPDATE `$tableName` SET `$column` = LEFT(`$column`, 1000) WHERE LENGTH(`$column`) > 1000;");
                 }
             } else {
                 DB::statement("UPDATE `$tableName` SET `$columns` = LEFT(`$columns`, 1000) WHERE LENGTH(`$columns`) > 1000;");
             }
         }

        // Actualizando tablas y columnas
        Schema::table('footer_pages', function (Blueprint $table) {
            $table->string('content', 1000)->change();
        });

        Schema::table('header_pages', function (Blueprint $table) {
            $table->string('content', 1000)->change();
        });

        Schema::table('tooltip_texts', function (Blueprint $table) {
            $table->string('description', 1000)->change();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('description', 1000)->nullable()->change();
        });

        Schema::table('certification_types', function (Blueprint $table) {
            $table->string('description', 1000)->nullable()->change();
        });

        Schema::table('competences', function (Blueprint $table) {
            $table->string('description', 1000)->nullable()->change();
        });

        Schema::table('learning_results', function (Blueprint $table) {
            $table->string('description', 1000)->nullable()->change();
        });
        Schema::table('course_types', function (Blueprint $table) {
            $table->string('description', 1000)->nullable()->change();
        });

        Schema::table('educational_program_types', function (Blueprint $table) {
            $table->string('description', 1000)->nullable()->change();
        });

        Schema::table('educational_resource_types', function (Blueprint $table) {
            $table->string('description', 1000)->nullable()->change();
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->string('status_reason', 1000)->nullable()->change();
            $table->string('description', 1000)->nullable()->change();
            $table->string('contact_information', 1000)->nullable()->change();
            $table->string('objectives', 1000)->nullable()->change();
            $table->string('evaluation_criteria', 1000)->nullable()->change();
            $table->string('featured_big_carrousel_description', 1000)->nullable()->change();
        });

        Schema::table('educational_programs', function (Blueprint $table) {
            $table->string('status_reason', 1000)->nullable()->change();
            $table->string('description', 1000)->nullable()->change();
        });

        Schema::table('educational_resources', function (Blueprint $table) {
            $table->string('description', 1000)->nullable()->change();
        });

        Schema::table('calls', function (Blueprint $table) {
            $table->string('description', 1000)->nullable()->change();
        });

        Schema::table('general_notifications', function (Blueprint $table) {
            $table->string('description', 1000)->change();
        });

        Schema::table('notifications_types', function (Blueprint $table) {
            $table->string('description', 1000)->nullable()->change();
        });

        Schema::table('course_blocks', function (Blueprint $table) {
            $table->string('description', 1000)->nullable()->change();
        });

        Schema::table('course_subblocks', function (Blueprint $table) {
            $table->string('description', 1000)->nullable()->change();
        });

        Schema::table('course_elements', function (Blueprint $table) {
            $table->string('description', 1000)->nullable()->change();
        });

        Schema::table('course_subelements', function (Blueprint $table) {
            $table->string('description', 1000)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revertir cambios en las tablas y columnas
        Schema::table('footer_pages', function (Blueprint $table) {
            $table->text('content')->change();
        });

        Schema::table('header_pages', function (Blueprint $table) {
            $table->text('content')->change();
        });

        Schema::table('tooltip_texts', function (Blueprint $table) {
            $table->text('description')->change();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('description', 256)->change();
        });

        Schema::table('certification_types', function (Blueprint $table) {
            $table->string('description', 255)->change();
        });

        Schema::table('competences', function (Blueprint $table) {
            $table->text('description')->change();
        });

        Schema::table('learning_results', function (Blueprint $table) {
            $table->text('description')->change();
        });

        Schema::table('course_types', function (Blueprint $table) {
            $table->string('description', 255)->change();
        });

        Schema::table('educational_program_types', function (Blueprint $table) {
            $table->string('description', 255)->change();
        });

        Schema::table('educational_resource_types', function (Blueprint $table) {
            $table->string('description', 255)->change();
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->text('status_reason')->change();
            $table->text('description')->change();
            $table->text('contact_information')->change();
            $table->text('objetives')->change();
            $table->text('evaluation_criteria')->change();
            $table->text('featured_big_carrousel_description')->change();
        });

        Schema::table('educational_programs', function (Blueprint $table) {
            $table->string('status_reason', 255)->change();
            $table->text('description')->change();
        });

        Schema::table('educational_resources', function (Blueprint $table) {
            $table->text('description')->change();
        });

        Schema::table('calls', function (Blueprint $table) {
            $table->text('description')->change();
        });

        Schema::table('email_notifications', function (Blueprint $table) {
            $table->text('body')->change();
        });

        Schema::table('general_notifications', function (Blueprint $table) {
            $table->text('description')->change();
        });

        Schema::table('notifications_types', function (Blueprint $table) {
            $table->string('description', 255)->change();
        });

        Schema::table('course_blocks', function (Blueprint $table) {
            $table->text('description')->change();
        });

        Schema::table('course_subblocks', function (Blueprint $table) {
            $table->text('description')->change();
        });

        Schema::table('course_elements', function (Blueprint $table) {
            $table->text('description')->change();
        });

        Schema::table('course_subelements', function (Blueprint $table) {
            $table->text('description')->change();
        });
    }
}
