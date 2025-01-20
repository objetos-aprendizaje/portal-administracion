<?php

namespace App\Jobs;

use App\Mail\SendNotification;
use App\Models\EmailNotificationsModel;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envía notificación por email
 */
class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;
    protected $subject;
    protected $template;
    protected $parameters;
    protected $emailNotificationUid;

    public function __construct($email, $subject, $parameters, $template, $emailNotificationUid = null)
    {
        $this->email = $email;
        $this->subject = $subject;
        $this->template = $template;
        $this->parameters = $parameters;
        $this->emailNotificationUid = $emailNotificationUid;
    }

    public function handle()
    {
        $notificationSent = false;
        try {
            Mail::to($this->email)->send(new SendNotification($this->subject, $this->parameters, $this->template));
            $notificationSent = true;
        } catch (Exception $e) {
            Log::error('Error sending email: ' . $e->getMessage());
            $notificationSent = false;
        }

        if($this->emailNotificationUid) {
            EmailNotificationsModel::where('uid', $this->emailNotificationUid)->update(['status' => $notificationSent ? 'SENT' : 'FAILED']);
        }
    }

}
