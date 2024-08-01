<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;

class ResetPasswordNotification extends Notification
{
    public $token;
    public $email;
    public $uid;

    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $url = URL::temporarySignedRoute(
            'password.reset',
            Carbon::now()->addMinutes(config('auth.passwords.users.expire')),
            ['token' => $this->token, 'email' => $this->email]
        );

        return (new MailMessage)
                    ->subject('Restablecer contraseña')
                    ->view('emails.reset_password_new', ['url' => $url]);
    }
}
