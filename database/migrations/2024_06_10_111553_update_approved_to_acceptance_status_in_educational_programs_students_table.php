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
        Schema::table('educational_programs_students', function (Blueprint $table) {
            // Primero eliminamos la columna 'approved'
            $table->dropColumn('approved');

            // Luego agregamos la nueva columna 'acceptance_status'
            $table->enum('acceptance_status', ['PENDING', 'ACCEPTED', 'REJECTED'])->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('educational_programs_students', function (Blueprint $table) {
            // Eliminamos la columna 'acceptance_status'
            $table->dropColumn('acceptance_status');

            // Luego agregamos de nuevo la columna 'approved'
            $table->boolean('approved')->nullable()->after('updated_at');
        });
    }
};
