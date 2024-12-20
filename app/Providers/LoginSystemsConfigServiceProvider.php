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
        if (env('DB_HOST') == 'image_build') return;

        if (!Schema::hasTable('general_options')) return;

        $parameters_login_systems = GeneralOptionsModel::whereIn('option_name', [
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
            'services.google.client_id' => $parameters_login_systems['google_client_id'],
            'services.google.client_secret' => $parameters_login_systems['google_client_secret'],
            'services.google.redirect' => env('APP_URL') . '/auth/callback/google',

            'services.facebook.client_id' => $parameters_login_systems['facebook_client_id'],
            'services.facebook.client_secret' => $parameters_login_systems['facebook_client_secret'],
            'services.facebook.redirect' => env('APP_URL') . '/auth/callback/facebook',

            'services.twitter.client_id' => $parameters_login_systems['twitter_client_id'],
            'services.twitter.client_secret' => $parameters_login_systems['twitter_client_secret'],
            'services.twitter.redirect' => env('APP_URL') . '/auth/callback/twitter',

            'services.linkedin-openid.client_id' => $parameters_login_systems['linkedin_client_id'],
            'services.linkedin-openid.client_secret' => $parameters_login_systems['linkedin_client_secret'],
            'services.linkedin-openid.redirect' => env('APP_URL') . '/auth/callback/linkedin-openid'
        ]);

        View::share('parameters_login_systems', $parameters_login_systems);

    }
}
