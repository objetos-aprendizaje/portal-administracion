<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningResultsModel extends Model
{
    use HasFactory;
    protected $table = 'learning_results';
    protected $primaryKey = 'uid';

    protected $fillable = [
        'uid', 'name', 'description', 'competence_uid', 'type'
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'uid' => 'string',
    ];

    public function competence()
    {
        return $this->belongsTo(CompetencesModel::class, 'competence_uid', 'uid');
    }

    public function certidigitalAssesments()
    {
        return $this->hasMany(CertidigitalAssesmentsModel::class, 'learning_result_uid', 'uid');
    }
}
