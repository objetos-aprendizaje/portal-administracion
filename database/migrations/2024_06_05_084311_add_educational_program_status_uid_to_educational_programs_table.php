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
        Schema::table('educational_programs', function (Blueprint $table) {
            $table->uuid('educational_program_status_uid')->nullable();
            // Especificar un nombre de clave foránea más corto
            $table->foreign('educational_program_status_uid', 'edu_prog_status_fk')
                  ->references('uid')->on('educational_program_statuses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('educational_programs', function (Blueprint $table) {
            $table->dropForeign(['educational_program_status_uid']);
            $table->dropColumn('educational_program_status_uid');
        });
    }
};
