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
        if (env('DB_HOST') == 'image_build') {
            return;
        }
        if (!Schema::hasTable('general_options')) {
            return;
        }

        $parametersEmailService = $this->getEmailParameters();
        $emailParametersDefined = $this->checkConfigEmailParameters($parametersEmailService);

        if (!$emailParametersDefined) {
            return;
        }

        Log::info('Email parameters are correctly configured', $parametersEmailService);
        $this->setConfigEmailServer($parametersEmailService);
    }


    private function setConfigEmailServer($parametersEmailService) {
        Config::set('mail.mailers.smtp.host', $parametersEmailService['smtp_server'] ?? null);
        Config::set('mail.mailers.smtp.port', $parametersEmailService['smtp_port'] ?? null);
        Config::set('mail.mailers.smtp.username', $parametersEmailService['smtp_user'] ?? null);
        Config::set('mail.mailers.smtp.password', $parametersEmailService['smtp_password'] ?? null);
        Config::set('mail.from.name', $parametersEmailService['smtp_name_from'] ?? env('MAIL_FROM_NAME'));
        Config::set('mail.mailers.smtp.encryption', $parametersEmailService['smtp_encryption'] ?? null);
        Config::set('mail.from.address', $parametersEmailService['smtp_address_from'] ?? null);
    }

    private function getEmailParameters() {
        return GeneralOptionsModel::whereIn('option_name', [
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
    }

    private function checkConfigEmailParameters($parametersEmailService) {

        $requiredParameters = [
            'smtp_server',
            'smtp_port',
            'smtp_user',
            'smtp_password',
            'smtp_address_from',
        ];

        foreach ($requiredParameters as $param) {
            if (is_null($parametersEmailService[$param])) {
                Log::error('Warning on boot: Email parameters are not configured');
                return false;
            }
        }

        return true;
    }
}
