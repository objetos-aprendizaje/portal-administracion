<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Create extension uuid-ossp if not exists
        DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');

        // Usamos DB::statement para realizar la conversión explícita
        DB::statement('ALTER TABLE reset_password_tokens ALTER COLUMN uid TYPE uuid USING uid::uuid');

        // Para establecer el valor por defecto como un UUID generado
        DB::statement('ALTER TABLE reset_password_tokens ALTER COLUMN uid SET DEFAULT uuid_generate_v4()');

        // Otros cambios a la columna si es necesario (NOT NULL)
        Schema::table('reset_password_tokens', function (Blueprint $table) {
            $table->uuid('uid')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('reset_password_tokens', function (Blueprint $table) {
            $table->dropColumn('uid');
        });
    }
};
