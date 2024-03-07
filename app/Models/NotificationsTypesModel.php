<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationsTypesModel extends Model
{
    use HasFactory;
    protected $table = 'notifications_types';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

}
