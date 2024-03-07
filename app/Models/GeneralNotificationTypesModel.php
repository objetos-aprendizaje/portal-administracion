<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class GeneralNotificationTypesModel extends Authenticatable
{
    use HasFactory;
    protected $table = 'general_notification_types';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = ['name'];


}
