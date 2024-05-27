<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendEmailJob;
use App\Models\EmailsSuggestionsModel;
use App\Models\SuggestionSubmissionEmailsModel;

class SendSuggestions extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-suggestions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía las sugerencias a los correos electrónicos que hay establecidos para ello';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Extraemos las sugerencias pendientes de enviar
        $emailSuggestions = EmailsSuggestionsModel::where('sent', 0)->get();

        // Si no hay sugerencias para enviar, terminamos la ejecución
        if (!$emailSuggestions->count()) return;

        // Extraemos los emails definidos para recibir las sugerencias
        $suggestionsSubmissionsEmails = SuggestionSubmissionEmailsModel::get();

        // Si no hay correos electrónicos para enviar sugerencias, terminamos la ejecución
        if (!$suggestionsSubmissionsEmails->count()) return;

        // Enviamos las sugerencias a cada correo electrónico
        foreach ($emailSuggestions as $emailSuggestion) {
            $this->sendEmailSuggestions($emailSuggestion, $suggestionsSubmissionsEmails);
        }
    }

    private function sendEmailSuggestions($emailSuggestion, $suggestionsSubmissionsEmails)
    {
        foreach ($suggestionsSubmissionsEmails as $suggestionsSubmissionsEmail) {
            try {
                $parameters = [
                    'name' => $emailSuggestion->name,
                    'email' => $emailSuggestion->email,
                    'message' => $emailSuggestion->message,
                ];

                dispatch(new SendEmailJob($suggestionsSubmissionsEmail->email, 'Nueva sugerencia', $parameters, 'emails.suggestion'));

                $emailSuggestion->sent = 1;
            } catch (\Exception $e) {
                Log::error('Error al enviar la sugerencia: ' . $e->getMessage());
            } finally {
                // Aseguramos que el modelo se guarde incluso si ocurre una excepción
                $emailSuggestion->save();
            }
        }
    }
}
