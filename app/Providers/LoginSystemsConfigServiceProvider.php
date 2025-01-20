<?php

namespace App\Providers;

use App\Models\GeneralOptionsModel;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class LoginSystemsConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (env('DB_HOST') == 'image_build') {
            return;
        }

        if (!Schema::hasTable('general_options')) {
            return;
        }

        $parametersLoginSystems = GeneralOptionsModel::whereIn('option_name', [
            'google_login_active',
            'google_client_id',
            'google_client_secret',
            'facebook_login_active',
            'facebook_client_id',
            'facebook_client_secret',
            'twitter_login_active',
            'twitter_client_id',
            'twitter_client_secret',
            'linkedin_login_active',
            'linkedin_client_id',
            'linkedin_client_secret'
        ])->pluck('option_value', 'option_name')->toArray();

        config([
            'services.google.client_id' => $parametersLoginSystems['google_client_id'],
            'services.google.client_secret' => $parametersLoginSystems['google_client_secret'],
            'services.google.redirect' => env('APP_URL') . '/auth/callback/google',

            'services.facebook.client_id' => $parametersLoginSystems['facebook_client_id'],
            'services.facebook.client_secret' => $parametersLoginSystems['facebook_client_secret'],
            'services.facebook.redirect' => env('APP_URL') . '/auth/callback/facebook',

            'services.twitter.client_id' => $parametersLoginSystems['twitter_client_id'],
            'services.twitter.client_secret' => $parametersLoginSystems['twitter_client_secret'],
            'services.twitter.redirect' => env('APP_URL') . '/auth/callback/twitter',

            'services.linkedin-openid.client_id' => $parametersLoginSystems['linkedin_client_id'],
            'services.linkedin-openid.client_secret' => $parametersLoginSystems['linkedin_client_secret'],
            'services.linkedin-openid.redirect' => env('APP_URL') . '/auth/callback/linkedin-openid'
        ]);

        View::share('parameters_login_systems', $parametersLoginSystems);

    }
}
