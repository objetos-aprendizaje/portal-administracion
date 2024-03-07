<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRolesModel extends Model
{
    use HasFactory;
    protected $table = 'user_roles';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    public function users()
    {
        return $this->belongsToMany(UsersModel::class, 'user_role_relationships', 'user_role_uid', 'user_uid');
    }
}
