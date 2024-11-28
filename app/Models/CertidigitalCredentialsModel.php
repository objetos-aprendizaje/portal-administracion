<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertidigitalCredentialsModel extends Model
{
    use HasFactory;
    protected $table = 'certidigital_credentials';
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
        'valid_from',
        'valid_until',
        'type'
    ];

    public function achievements()
    {
        return $this->hasMany(CertidigitalAchievementsModel::class, 'certidigital_credential_uid', 'uid');
    }

    public function activities()
    {
        return $this->hasMany(CertidigitalActivitiesModel::class, 'certidigital_credential_uid', 'uid');
    }
}
