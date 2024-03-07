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
        Schema::table('user_role_relationships', function (Blueprint $table) {
            // Primero eliminamos las restricciones anteriores para poder modificarlas
            $table->dropForeign(['user_uid']);
            $table->dropForeign(['user_role_uid']);

            // Ahora las añadimos de nuevo pero con las opciones en cascada
            $table->foreign('user_uid')
                  ->references('uid')
                  ->on('users')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->foreign('user_role_uid')
                  ->references('uid')
                  ->on('user_roles')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_role_relationships', function (Blueprint $table) {
            // Aquí revertimos los cambios. Eliminamos las nuevas y añadimos las viejas
            $table->dropForeign(['user_uid']);
            $table->dropForeign(['user_role_uid']);

            $table->foreign('user_uid')
                  ->references('uid')
                  ->on('users');

            $table->foreign('user_role_uid')
                  ->references('uid')
                  ->on('user_roles');
        });
    }
};
