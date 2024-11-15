<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertidigitalAssesmentsModel extends Model
{
    use HasFactory;

    protected $table = 'certidigital_assesments';

    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $casts = [
        'uid' => 'string',
    ];

    public $incrementing = false;

    protected $fillable = [
        'uid',
        'id',
        'title',
        'description',
        'certidigital_achievement_uid',
        'certidigital_credential_uid',
        'course_block_uid',
        'learning_result_uid',
        'course_uid',
    ];
}
