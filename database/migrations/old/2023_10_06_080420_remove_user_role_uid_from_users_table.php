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
        Schema::table('users', function (Blueprint $table) {
            // Asegúrate de eliminar primero cualquier restricción de clave foránea que pueda existir
            // Ahora borra la columna
            $table->dropColumn('user_rol_uid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }
};
