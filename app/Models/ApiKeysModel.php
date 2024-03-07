<?php

namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApiKeysModel extends Authenticatable
{
    use HasFactory;
    protected $table = 'api_keys';
    protected $primaryKey = 'uid';
    protected $fillable = ['name', 'api_key'];

    protected $casts = [
        'uid' => 'string',
    ];
}
