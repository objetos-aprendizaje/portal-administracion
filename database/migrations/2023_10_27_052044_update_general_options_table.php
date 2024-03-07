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
    public function up(): void
    {
        // Borrar registros con option_name 'color_primary' y 'color_secondary'
        DB::table('general_options')->where('option_name', 'color_primary')->delete();
        DB::table('general_options')->where('option_name', 'color_secondary')->delete();

        // Insertar nuevos registros
        DB::table('general_options')->insert([
            ['option_name' => 'color_1'],
            ['option_name' => 'color_2'],
            ['option_name' => 'color_3'],
            ['option_name' => 'color_4'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('general_options')->where('option_name', 'color_1')->delete();
        DB::table('general_options')->where('option_name', 'color_2')->delete();
        DB::table('general_options')->where('option_name', 'color_3')->delete();
        DB::table('general_options')->where('option_name', 'color_4')->delete();

    }
};
