<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::table('general_options')
            ->where('option_name', 'carrousel_name')
            ->update(['option_value' => 'Bienvenidos al POA']);

        DB::table('general_options')
            ->where('option_name', 'carrousel_description')
            ->update(['option_value' => 'Descubre todos los cursos disponibles']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};