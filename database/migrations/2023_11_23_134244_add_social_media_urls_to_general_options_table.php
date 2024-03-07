<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddSocialMediaUrlsToGeneralOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $urls = [
            'facebook_url',
            'x_url',
            'youtube_url',
            'instagram_url',
            'telegram_url',
            'linkedin_url'
        ];

        foreach ($urls as $url) {
            DB::table('general_options')->insert([
                'option_name' => $url
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('general_options')
          ->whereIn('option_name', [
              'facebook_url',
              'x_url',
              'youtube_url',
              'instagram_url',
              'telegram_url',
              'linkedin_url'
          ])->delete();
    }
}
