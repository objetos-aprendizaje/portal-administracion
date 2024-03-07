<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;


class HeaderPagesModel extends Authenticatable
{
    use HasFactory;
    protected $table = 'header_pages';
    protected $primaryKey = 'uid';
    protected $keyType = 'string';

    protected $fillable = ['name', 'content'];

}
