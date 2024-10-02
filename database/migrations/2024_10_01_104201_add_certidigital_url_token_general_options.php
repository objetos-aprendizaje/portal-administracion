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
        DB::table('general_options')->insert([
            'option_name' => 'certidigital_url_token',
            'option_value' => null,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
