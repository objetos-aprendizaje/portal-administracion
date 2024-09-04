<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Jobs\SendEmailJob;
use Illuminate\Support\Facades\Queue;
use App\Models\EmailsSuggestionsModel;
use Illuminate\Support\Facades\Artisan;
use App\Models\SuggestionSubmissionEmailsModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SendSuggestionsCommandTest extends TestCase
{
    use RefreshDatabase;

    /** 
     * @test 
     * Este test verifica que se envían sugerencias y se actualiza su estado a enviado.
     */
    public function testSendsSuggestionsAndMarksAsSent()
    {
        // Crear sugerencias no enviadas
        $emailSuggestion = EmailsSuggestionsModel::factory()->create(['sent' => 0]);

        // Crear correos electrónicos de destino para sugerencias
        $suggestionsSubmissionsEmail = SuggestionSubmissionEmailsModel::factory()->create();

        // Fake para evitar el envío real de correos
        Queue::fake();

        // Ejecutar el comando
        Artisan::call('app:send-suggestions');

        // Verificar que el trabajo de envío de email fue despachado
        Queue::assertPushed(SendEmailJob::class, function ($job) use ($emailSuggestion, $suggestionsSubmissionsEmail) {
            $reflectionClass = new \ReflectionClass($job);
    
            $emailProperty = $reflectionClass->getProperty('email');
            $emailProperty->setAccessible(true);
            $email = $emailProperty->getValue($job);
    
            $parametersProperty = $reflectionClass->getProperty('parameters');
            $parametersProperty->setAccessible(true);
            $parameters = $parametersProperty->getValue($job);
    
            return $email === $suggestionsSubmissionsEmail->email &&
                   $parameters['name'] === $emailSuggestion->name &&
                   $parameters['email'] === $emailSuggestion->email &&
                   $parameters['message'] === $emailSuggestion->message;
        });

        // Verificar que la sugerencia ha sido marcada como enviada
        $this->assertDatabaseHas('emails_suggestions', [
            'uid' => $emailSuggestion->uid,
            'sent' => 1,
        ]);
    }

    /** 
     * @test 
     * Este test verifica que si no hay sugerencias no se envían correos.
     */
    public function testDoesNotSendEmailsIfNoSuggestions()
    {
        // No crear sugerencias

        // Fake para evitar el envío real de correos
        Queue::fake();

        // Ejecutar el comando
        Artisan::call('app:send-suggestions');

        // Verificar que no se haya enviado ninguna sugerencia
        Queue::assertNotPushed(SendEmailJob::class);
    }

    /** 
     * @test 
     * Este test verifica que si no hay correos electrónicos de destino no se envían correos.
     */
    public function testDoesNotSendEmailsIfNoSubmissionEmails()
    {
        // Crear sugerencias no enviadas
        $emailSuggestion = EmailsSuggestionsModel::factory()->create([
            'sent' => 0,
            'email'=>"",
        ]);

        // No crear correos electrónicos de destino

        // Fake para evitar el envío real de correos
        Queue::fake();

        // Ejecutar el comando
        Artisan::call('app:send-suggestions');

        // Verificar que no se haya enviado ninguna sugerencia
        Queue::assertNotPushed(SendEmailJob::class);

        // Verificar que la sugerencia aún no ha sido marcada como enviada
        $this->assertDatabaseHas('emails_suggestions', [
            'uid' => $emailSuggestion->uid,
            'sent' => 0,
        ]);
    }
}
