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
        Schema::table('general_options', function (Blueprint $table) {
            DB::table('general_options')->insert([
                ['option_name' => 'cas_active', 'option_value' => 0],
                ['option_name' => 'rediris_active', 'option_value' => 0],
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_options', function (Blueprint $table) {
            DB::table('general_options')->where('option_name', 'cas_active')->delete();
            DB::table('general_options')->where('option_name', 'rediris_active')->delete();
        });
    }
};
