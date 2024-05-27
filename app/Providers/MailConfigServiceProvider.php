<?php

namespace App\Providers;

use App\Models\GeneralOptionsModel;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class MailConfigServiceProvider extends ServiceProvider
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

        if (!Cache::has('parameters_email_service') || empty(Cache::get('parameters_email_service'))) {
            $parameters_email_service = GeneralOptionsModel::whereIn('option_name', [
                'smtp_server',
                'smtp_port',
                'smtp_user',
                'smtp_password',
                'smtp_name_from'
            ])->get()->toArray();
            $config = [];

            foreach ($parameters_email_service as $parameter_email_service) {
                $config[$parameter_email_service['option_name']] = $parameter_email_service['option_value'];
            }

            Cache::put('parameters_email_service', $config, 60 * 24); // Cache for 24 hours
        }

        $parameters_email_service = Cache::get('parameters_email_service');

        Config::set('mail.mailers.smtp.encryption', env('MAIL_ENCRYPTION'));
        Config::set('mail.from.address', $parameters_email_service['smtp_user'] ?? null);
        Config::set('mail.from.name', $parameters_email_service['smtp_name_from'] ?? env('MAIL_FROM_NAME'));


        Config::set('mail.mailers.smtp.host', $parameters_email_service['smtp_server'] ?? null);
        Config::set('mail.mailers.smtp.port', $parameters_email_service['smtp_port'] ?? null);
        Config::set('mail.mailers.smtp.username', $parameters_email_service['smtp_user'] ?? null);
        Config::set('mail.mailers.smtp.password', $parameters_email_service['smtp_password'] ?? null);
    }
}
