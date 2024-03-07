<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class MailService
{

    public function sendResetPasswordMail($user, $data)
    {


        Mail::send('emails.reset_password', $data, function ($m) use ($user) {
            $m->to($user->email, $user->first_name . ' ' . $user->last_name)->subject('Restablecer contraseña');
        });


    }
}
