<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertificationTypesModel extends Model
{
    use HasFactory;
    protected $table = 'certification_types';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';
    protected $fillable = [
        'uid',
        'name',
    ];

}
