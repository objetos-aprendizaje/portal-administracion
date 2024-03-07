<?php

namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ResetPasswordTokensModel extends Authenticatable
{
    const UPDATED_AT = null;

    use HasFactory;
    protected $table = 'reset_password_tokens';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = ['token', 'uid_user', 'token'];

}
