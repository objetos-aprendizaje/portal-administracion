<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DestinationsEmailNotificationsRolesModel extends Model
{
    use HasFactory;
    protected $table = 'destinations_email_notifications_roles';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = [
        'uid',
        'email_notification_uid',
        'rol_uid',
    ];

    public $timestamps = false;

}
