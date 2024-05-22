<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class GeneralNotificationsAutomaticUsersModel extends Model
{
    use HasFactory;

    protected $table = 'general_notifications_automatic_users';

    protected $primaryKey = 'uid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'uid',
        'general_notifications_automatic_uid',
        'user_uid',
        'is_read'
    ];
}
