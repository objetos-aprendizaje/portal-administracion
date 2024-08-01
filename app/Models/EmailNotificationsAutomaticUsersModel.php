<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class EmailNotificationsAutomaticUsersModel extends Model
{
    use HasFactory;

    protected $table = 'email_notifications_automatic_users';

    protected $primaryKey = 'uid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $timestamps = false;

    protected $fillable = [
        'uid',
        'email_notification_automatic_uid',
        'user_uid',
    ];
}
