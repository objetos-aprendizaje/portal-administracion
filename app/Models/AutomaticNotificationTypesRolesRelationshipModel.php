<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomaticNotificationTypesRolesRelationshipModel extends Model
{
    use HasFactory;

    protected $table = 'automatic_notification_types_roles_relationship';

    protected $fillable = [
        'automatic_notification_type_uid',
        'user_role_uid'
    ];

    public $timestamps = false;

    protected $primaryKey = ['automatic_notification_type_uid', 'user_role_uid'];

    public $incrementing = false;

}
