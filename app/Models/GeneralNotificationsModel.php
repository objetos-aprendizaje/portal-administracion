<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralNotificationsModel extends Model
{
    use HasFactory;
    protected $table = 'general_notifications';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = [
        'title', 'description', 'start_date', 'end_date', 'type', 'general_notification_type_uid'
    ];


    public function roles()
    {
        return $this->belongsToMany(
            UserRolesModel::class,
            'destinations_general_notifications_roles',
            'general_notification_uid',
            'rol_uid',
            'uid',
            'uid'
        );
    }

    public function users()
    {
        return $this->belongsToMany(
            UsersModel::class,
            'destinations_general_notifications_users',
            'general_notification_uid',
            'user_uid',
            'uid',
            'uid'
        );
    }

    public function generalNotificationType()
    {
        return $this->belongsTo(GeneralNotificationTypesModel::class, 'general_notification_type_uid', 'uid');
    }

}
