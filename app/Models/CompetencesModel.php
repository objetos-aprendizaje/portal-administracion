<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetencesModel extends Model
{
    use HasFactory;
    protected $table = 'competences';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = ['uid', 'name', 'parent_competence_uid', 'is_multi_select', 'origin_code'];

    public function parentCompetence()
    {
        return $this->belongsTo(CompetencesModel::class, 'parent_competence_uid')->with('parentCompetence')->whereNull('parent_competence_uid')->orderBy('name', 'ASC');
    }
    public function subcompetences()
    {
        return $this->hasMany(CompetencesModel::class, 'parent_competence_uid', 'uid')
            ->with(['subcompetences', 'learningResults']);
    }

    public function learningResults()
    {
        return $this->hasMany(LearningResultsModel::class, 'competence_uid', 'uid');
    }
}
