<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InsertCertidigitalOptionsIntoGeneralOptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $options = [
            ['option_name' => 'certidigital_url', 'option_value' => null],
            ['option_name' => 'certidigital_client_id', 'option_value' => null],
            ['option_name' => 'certidigital_client_secret', 'option_value' => null],
            ['option_name' => 'certidigital_username', 'option_value' => null],
            ['option_name' => 'certidigital_password', 'option_value' => null],
        ];

        DB::table('general_options')->insert($options);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('general_options')->whereIn('option_name', [
            'certidigital_url',
            'certidigital_client_id',
            'certidigital_client_secret',
            'certidigital_username',
            'certidigital_password',
        ])->delete();
    }
}
