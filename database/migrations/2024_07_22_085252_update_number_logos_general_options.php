<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('general_options')->where('option_name', 'poa_logo')->update(['option_name' => 'poa_logo_1']);

        DB::table('general_options')->insert([
            'option_name' => 'poa_logo_2',
        ]);

        DB::table('general_options')->insert([
            'option_name' => 'poa_logo_3',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
