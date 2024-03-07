<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InsertFontOptionsIntoGeneralOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Asegúrate de que tu tabla se llame 'general_options'
        $options = [
            // Regular
            ['option_name' => 'truetype_regular_file_path'],
            ['option_name' => 'woff_regular_file_path'],
            ['option_name' => 'woff2_regular_file_path'],
            ['option_name' => 'embedded_opentype_regular_file_path'],
            ['option_name' => 'opentype_regular_input_file'],
            ['option_name' => 'svg_regular_file_path'],

            // Medium
            ['option_name' => 'truetype_medium_file_path'],
            ['option_name' => 'woff_medium_file_path'],
            ['option_name' => 'woff2_medium_file_path'],
            ['option_name' => 'embedded_opentype_medium_file_path'],
            ['option_name' => 'opentype_medium_file_path'],
            ['option_name' => 'svg_medium_file_path'],

            // Bold
            ['option_name' => 'truetype_bold_file_path'],
            ['option_name' => 'woff_bold_file_path'],
            ['option_name' => 'woff2_bold_file_path'],
            ['option_name' => 'embedded_opentype_bold_file_path'],
            ['option_name' => 'opentype_bold_file_path'],
            ['option_name' => 'svg_bold_file_path'],

        ];

        foreach ($options as $option) {
            DB::table('general_options')->insert($option);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('general_options')->whereIn('option_name', [

            // Regular
            'truetype_regular_file_path',
            'woff_regular_file_path',
            'woff2_regular_file_path',
            'embedded_opentype_regular_file_path',
            'opentype_regular_input_file',
            'svg_regular_file_path',


            // Medium
            'truetype_medium_file_path',
            'woff_medium_file_path',
            'woff2_medium_file_path',
            'embedded_opentype_medium_file_path',
            'opentype_medium_file_path',
            'svg_medium_file_path',


            // Bold
            'truetype_bold_file_path',
            'woff_bold_file_path',
            'woff2_bold_file_path',
            'embedded_opentype_bold_file_path',
            'opentype_bold_file_path',
            'svg_bold_file_path',
        ])->delete();
    }
}
