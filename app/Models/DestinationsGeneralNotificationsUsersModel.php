<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DestinationsGeneralNotificationsUsersModel extends Model
{
    use HasFactory;
    protected $table = 'destinations_general_notifications_users';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = [
        'uid',
        'general_notification_uid',
        'user_uid',
    ];

    public $timestamps = false;


}
