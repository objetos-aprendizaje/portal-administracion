<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomaticResourceAprovalUsersModel extends Model
{
    use HasFactory;
    protected $table = 'automatic_resource_approval_users';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

}
