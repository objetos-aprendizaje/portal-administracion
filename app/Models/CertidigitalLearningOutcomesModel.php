<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertidigitalLearningOutcomesModel extends Model
{
    use HasFactory;

    protected $table = 'certidigital_learning_outcomes';

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
        'certidigital_achievement_uid'
    ];
}
