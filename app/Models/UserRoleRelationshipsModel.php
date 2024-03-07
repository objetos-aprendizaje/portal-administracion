<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRoleRelationshipsModel extends Model
{
    use HasFactory;
    protected $table = 'user_role_relationships';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

}
