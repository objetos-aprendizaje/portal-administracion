<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UserGeneralNotificationTypesDisabledModel extends Authenticatable
{
    use HasFactory;
    protected $table = 'user_general_notification_types_disabled';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = ['user_uid', 'notification_type_uid'];

}
