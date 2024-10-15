<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Foundation\Auth\User as Authenticatable;

class UsersVisitsModel extends Authenticatable
{
    use HasFactory;

    protected $table = 'courses_visits';

    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $casts = [
        'uid' => 'string',
    ];

    public $incrementing = false;

    protected $fillable = ['uid', 'user_uid', 'access_date', 'course_uid'];

    public $timestamps = false;
}
