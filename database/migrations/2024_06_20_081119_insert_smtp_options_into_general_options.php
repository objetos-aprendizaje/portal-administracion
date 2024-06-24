<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InsertSmtpOptionsIntoGeneralOptions extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::table('general_options')->insert([
            [
                'option_name' => 'smtp_address_from',
                'option_value' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'option_name' => 'smtp_encryption',
                'option_value' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::table('general_options')->whereIn('option_name', ['smtp_from', 'smtp_encryption'])->delete();
    }
}
