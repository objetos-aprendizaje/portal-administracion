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
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // Primero eliminamos cualquier restricción 'CHECK' anterior en la columna 'payment_mode', si existe
            DB::statement('ALTER TABLE courses DROP CONSTRAINT IF EXISTS payment_mode_check');

            // Luego cambiamos la columna 'payment_mode' a varchar (o string)
            $table->string('payment_mode')->change();

            // Finalmente, añadimos la nueva restricción 'CHECK' para validar los valores permitidos
            DB::statement("ALTER TABLE courses ADD CONSTRAINT payment_mode_check CHECK (payment_mode IN ('INSTALLMENT_PAYMENT', 'SINGLE_PAYMENT'))");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
