<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;




class ResetPasswordNotificationTest extends TestCase
{
    use RefreshDatabase;
    protected $token;
    protected $email;
    public function setUp(): void
    {

        parent::setUp();
        $this->withoutMiddleware();
        $this->token = 'sample-token';
        $this->email = 'user@example.com';

    }

/**
 * @testdox Genera Notificaciones
**/

    public function testConstructsWithTokenAndEmail()
    {

        $notification = new ResetPasswordNotification($this->token, $this->email);

        $this->assertEquals($this->token, $notification->token);
        $this->assertEquals($this->email, $notification->email);
    }


/** @test Crear mensaje Mail*/
    public function testCreatesAMailMessage()
    {
        // Configura el tiempo de expiración para las contraseñas
        Config::set('auth.passwords.users.expire', 60);

        // Simula la llamada a temporarySignedRoute con argumentos específicos
        URL::shouldReceive('temporarySignedRoute')
            ->once()
            ->withArgs(function ($name, $expiration, $parameters) {
                return $name === 'password.reset' &&
                    $expiration instanceof Carbon &&
                    $parameters['token'] === $this->token &&
                    $parameters['email'] === $this->email;
            })
            ->andReturn('http://example.com/reset-password');

        // Crea una instancia de la notificación
        $notification = new ResetPasswordNotification($this->token, $this->email);

        // Crea un objeto notifiable simulado
        $notifiable = new class {
            public function routeNotificationFor($driver)
            {
                return 'user@example.com';
            }
        };

        // Llama al método toMail para obtener el mensaje de correo
        $mailMessage = $notification->toMail($notifiable);

        // Verifica que el objeto MailMessage sea del tipo correcto
        $this->assertInstanceOf(MailMessage::class, $mailMessage);

        // Verifica que el asunto del mensaje sea correcto
        $this->assertEquals('Restablecer contraseña', $mailMessage->subject);

        // Verifica que la vista utilizada sea la correcta
        $this->assertEquals('emails.reset_password_new', $mailMessage->view);

        // Verifica que la URL esté presente en los datos de la vista
        $this->assertArrayHasKey('url', $mailMessage->viewData);

        // Verifica que la URL generada sea la esperada
        $this->assertEquals('http://example.com/reset-password', $mailMessage->viewData['url']);

    }

/** @test Enviar notificación*/
    public function testSendsNotificationViaMail()
    {
        // Crea una instancia de la notificación
        $notification = new ResetPasswordNotification($this->token, $this->email);

        // Crea un objeto notifiable simulado
        $notifiable = new class {
            public function routeNotificationFor($driver)
            {
                return 'user@example.com';
            }
        };

        // Llama al método via para obtener los canales de notificación
        $channels = $notification->via($notifiable);

        // Verifica que el canal devuelto sea 'mail'
        $this->assertContains('mail', $channels);
    }



}
