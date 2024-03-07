<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailNotificationsModel extends Model
{
    use HasFactory;
    protected $table = 'email_notifications';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';


    public function roles()
    {
        return $this->belongsToMany(
            UserRolesModel::class,
            'destinations_email_notifications_roles',
            'email_notification_uid',
            'rol_uid',
            'uid',
            'uid'
        );
    }

    public function users()
    {
        return $this->belongsToMany(
            UsersModel::class,
            'destinations_email_notifications_users',
            'email_notification_uid',
            'user_uid',
            'uid',
            'uid'
        );
    }

}
