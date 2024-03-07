<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddSocialOptionsToGeneralOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('general_options')->insert([
            ['option_name' => 'google_login_active'],
            ['option_name' => 'google_client_id'],
            ['option_name' => 'google_client_secret'],

            ['option_name' => 'facebook_login_active'],
            ['option_name' => 'facebook_login_active'],
            ['option_name' => 'facebook_client_id'],
            ['option_name' => 'facebook_client_secret'],

            ['option_name' => 'twitter_login_active'],
            ['option_name' => 'twitter_client_id'],
            ['option_name' => 'twitter_client_secret'],

            ['option_name' => 'linkedin_login_active'],
            ['option_name' => 'linkedin_client_id'],
            ['option_name' => 'linkedin_client_secret'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('general_options')->whereIn('option_name', [
            'google_login_active',
            'google_client_id',
            'google_client_secret',
            'facebook_login_active',
            'facebook_client_id',
            'facebook_client_secret',
            'twitter_client_id',
            'twitter_client_secret',
            'linkedin_client_id',
            'linkedin_client_secret',
        ])->delete();
    }
}
