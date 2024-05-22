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
        // Cambiar el nombre de la tabla
        Schema::rename('educational_resource_accesses', 'educational_resource_access');

        // Añadir nueva columna 'user_uid'
        Schema::table('educational_resource_access', function (Blueprint $table) {
            $table->string('user_uid')->nullable()->after('uid'); // Reemplaza 'some_existing_column' con la columna adecuada
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir la adición de la columna 'user_uid'
        Schema::table('educational_resource_access', function (Blueprint $table) {
            $table->dropColumn('user_uid');
        });

        // Cambiar el nombre de la tabla de vuelta a su nombre original
        Schema::rename('educational_resource_access', 'educational_resource_accesses');

    }
};
