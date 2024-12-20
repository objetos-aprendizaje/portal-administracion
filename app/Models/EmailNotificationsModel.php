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

    protected $fillable = [
        'uid', 'subject', 'body', 'type', 'send_date', 'status', 'notification_type_uid', 'schedule_notification'
    ];

    protected $casts = [
        'uid' => 'string',
    ];

    public $incrementing = false;

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
    public function emailNotificationType()
    {
        return $this->belongsTo(NotificationsTypesModel::class, 'notification_type_uid', 'uid');
    }

}
