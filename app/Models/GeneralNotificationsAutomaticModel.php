<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class GeneralNotificationsAutomaticModel extends Model
{
    use HasFactory;

    protected $table = 'general_notifications_automatic';

    protected $primaryKey = 'uid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'uid',
        'title',
        'description',
        'entity_uid',
        'automatic_notification_type_uid'
    ];

    public function users()
    {
        return $this->belongsToMany(UsersModel::class, 'general_notifications_automatic_users', 'general_notifications_automatic_uid', 'user_uid');
    }

    public function automaticNotificationType() {
        return $this->belongsTo(
            AutomaticNotificationTypesModel::class,
            'automatic_notification_type_uid',
            'uid'
        );
    }
}
