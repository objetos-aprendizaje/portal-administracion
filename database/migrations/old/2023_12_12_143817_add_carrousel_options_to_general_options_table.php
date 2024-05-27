<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddCarrouselOptionsToGeneralOptionsTable extends Migration
{
    public function up()
    {
        DB::table('general_options')->insert([
            ['option_name' => 'carrousel_image_path', 'option_value' => null],
            ['option_name' => 'carrousel_title', 'option_value' => null],
            ['option_name' => 'carrousel_description', 'option_value' => null],
        ]);
    }

    public function down()
    {
        DB::table('general_options')->where('option_name', 'carrousel_image_path')->delete();
        DB::table('general_options')->where('option_name', 'carrousel_title')->delete();
        DB::table('general_options')->where('option_name', 'carrousel_description')->delete();
    }
}
