<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertidigitalActivitiesModel extends Model
{
    use HasFactory;

    protected $table = 'certidigital_activities';

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
        'organization_oid',
        'default_language',
        'language_codes',
        'certidigital_achievement_uid'
    ];
}
