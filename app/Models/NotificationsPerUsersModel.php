<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationsPerUsersModel extends Model
{
    use HasFactory;
    protected $table = 'user_general_notifications';
    protected $primaryKey = 'uid';
    protected $fillable = ['user_id', 'uid', 'general_notification_uid'];

    protected $keyType = 'string';






}
