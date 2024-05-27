<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailJob;
use App\Models\EmailNotificationsAutomaticModel;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendEmailNotificationsAutomatic extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-email-notifications-automatic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía las notificaciones por email pendientes';


    /**
     * Execute the console command.
     */
    public function handle()
    {

        $emailNotificationsAutomatic = EmailNotificationsAutomaticModel::where('sent', false)->where('sending_attempts', '<', 3)->with('user')->orderBy('created_at', 'ASC')->get();

        foreach ($emailNotificationsAutomatic as $notification) {
            $parameters = json_decode($notification->parameters, true);

            try {
                dispatch(new SendEmailJob($notification['user']->email, $notification['subject'], $parameters, 'emails.'.$notification['template']));
            } catch (Exception $e) {
                Log::error('Error al despachar el trabajo de envío de notificación: '.$e->getMessage());
                $notification->sending_attempts++;
                $notification->save();
                continue;
            }

            $notification->sent = true;
            $notification->save();
        }
    }
}
