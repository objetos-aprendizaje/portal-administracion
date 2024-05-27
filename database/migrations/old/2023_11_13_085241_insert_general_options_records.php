<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {

        DB::table('general_options')->insert([
            ['option_name' => 'redsys_commerce_code'],
            ['option_name' => 'redsys_terminal'],
            ['option_name' => 'redsys_currency'],
            ['option_name' => 'redsys_transaction_type'],
            ['option_name' => 'redsys_encryption_key'],
        ]);
    }

    public function down(): void
    {

        DB::table('general_options')->whereIn('option_name', [
            'redsys_commerce_code',
            'redsys_terminal',
            'redsys_currency',
            'redsys_transaction_type',
            'redsys_encryption_key',
        ])->delete();
    }
};
