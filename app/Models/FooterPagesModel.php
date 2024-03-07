<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;


class FooterPagesModel extends Authenticatable
{
    use HasFactory;
    protected $table = 'footer_pages';
    protected $primaryKey = 'uid';
    protected $keyType = 'string';

    protected $fillable = ['name', 'content'];

}
