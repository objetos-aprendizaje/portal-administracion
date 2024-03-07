<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LmsSystemsModel extends Model
{
    use HasFactory;
    protected $table = 'lms_systems';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = [
        'uid',
        'name',
        'identifier'
    ];

}
