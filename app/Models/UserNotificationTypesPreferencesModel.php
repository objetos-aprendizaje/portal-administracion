<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UserNotificationTypesPreferencesModel extends Authenticatable
{
    use HasFactory;
    protected $table = 'user_notification_types_preferences';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = ['user_uid', 'notification_type_uid'];

}
