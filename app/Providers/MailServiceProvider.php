<?php

namespace App\Providers;

use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class MailServiceProvider extends ServiceProvider {
    /**
     * Register services.
     */
    public function register(): void {
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void {
        if (env('DB_HOST') == 'image_build') return;
        if (!Schema::hasTable('general_options')) return;

        $parameters_email_service = $this->getEmailParameters();
        $emailParametersDefined = $this->checkConfigEmailParameters($parameters_email_service);

        if (!$emailParametersDefined) return;

        Log::info('Email parameters are correctly configured', $parameters_email_service);
        $this->setConfigEmailServer($parameters_email_service);
    }


    private function setConfigEmailServer($parameters_email_service) {
        Config::set('mail.mailers.smtp.host', $parameters_email_service['smtp_server'] ?? null);
        Config::set('mail.mailers.smtp.port', $parameters_email_service['smtp_port'] ?? null);
        Config::set('mail.mailers.smtp.username', $parameters_email_service['smtp_user'] ?? null);
        Config::set('mail.mailers.smtp.password', $parameters_email_service['smtp_password'] ?? null);
        Config::set('mail.from.name', $parameters_email_service['smtp_name_from'] ?? env('MAIL_FROM_NAME'));
        Config::set('mail.mailers.smtp.encryption', $parameters_email_service['smtp_encryption'] ?? null);
        Config::set('mail.from.address', $parameters_email_service['smtp_address_from'] ?? null);
    }

    private function getEmailParameters() {
        $parameters_email_service = GeneralOptionsModel::whereIn('option_name', [
            'smtp_server',
            'smtp_port',
            'smtp_user',
            'smtp_password',
            'smtp_address_from',
            'smtp_name_from',
            'smtp_encryption',
        ])->get()->mapWithKeys(function ($item) {
            return [$item['option_name'] => $item['option_value']];
        })->toArray();

        return $parameters_email_service;
    }

    private function checkConfigEmailParameters($parameters_email_service) {

        $required_parameters = [
            'smtp_server',
            'smtp_port',
            'smtp_user',
            'smtp_password',
            'smtp_address_from',
        ];

        foreach ($required_parameters as $param) {
            if (is_null($parameters_email_service[$param])) {
                Log::error('Warning on boot: Email parameters are not configured');
                return false;
            }
        }

        return true;
    }
}
