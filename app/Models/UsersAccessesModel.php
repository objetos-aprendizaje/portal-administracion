<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Foundation\Auth\User as Authenticatable;

class UsersAccessesModel extends Authenticatable
{
    use HasFactory;

    protected $table = 'users_accesses';

    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $casts = [
        'uid' => 'string',
    ];

    public $incrementing = false;

    protected $fillable = ['uid', 'user_uid', 'date'];

    public $timestamps = false;
}
