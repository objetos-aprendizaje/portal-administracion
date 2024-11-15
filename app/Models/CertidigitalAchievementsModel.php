<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertidigitalAchievementsModel extends Model
{
    use HasFactory;

    protected $table = 'certidigital_achievements';

    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $casts = [
        'uid' => 'string',
    ];

    public $incrementing = false;

    protected $fillable = ['uid', 'id', 'title', 'description', 'certidigital_credential_uid', 'certidigital_achievement_uid'];

    public function activities()
    {
        return $this->hasMany(CertidigitalActivitiesModel::class, 'certidigital_achievement_uid', 'uid');
    }

    public function assesments()
    {
        return $this->hasMany(CertidigitalAssesmentsModel::class, 'certidigital_achievement_uid', 'uid');
    }

    public function learningOutcomes()
    {
        return $this->hasMany(CertidigitalLearningOutcomesModel::class, 'certidigital_achievement_uid', 'uid');
    }

    public function subAchievements()
    {
        return $this->hasMany(CertidigitalAchievementsModel::class, 'certidigital_achievement_uid', 'uid');
    }
}
