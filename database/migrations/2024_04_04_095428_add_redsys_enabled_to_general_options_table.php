<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::table('general_options')->insert([
            'option_name' => 'redsys_enabled',
            'option_value' => false
        ]);
    }
    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::table('general_options')->where('option_name', 'redsys_enabled')->delete();
    }
};
