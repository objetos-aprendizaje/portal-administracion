<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogsModel extends Model
{
    use HasFactory;
    protected $table = 'logs';

    public function user()
    {
        return $this->hasOne(
            UsersModel::class,
            'uid',
            'user_uid'
        );
    }
}
