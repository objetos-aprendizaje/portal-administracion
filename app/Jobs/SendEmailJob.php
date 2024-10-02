<?php

namespace App\Jobs;

use App\Mail\SendNotification;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;
    protected $subject;
    protected $template;
    protected $parameters;

    public function __construct($email, $subject, $parameters, $template)
    {
        $this->email = $email;
        $this->subject = $subject;
        $this->template = $template;
        $this->parameters = $parameters;
    }

    public function handle()
    {
        try {
            Mail::to($this->email)->send(new SendNotification($this->subject, $this->parameters, $this->template));
        } catch (Exception $e) {
            Log::error('Error sending email: ' . $e->getMessage());
        }
    }

}
