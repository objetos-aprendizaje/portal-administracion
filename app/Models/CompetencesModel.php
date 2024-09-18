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

    protected $fillable = ['uid', 'name', 'description', 'parent_competence_uid', 'origin_code', 'competence_framework_uid'];

    public function parentCompetence()
    {
        return $this->belongsTo(CompetencesModel::class, 'parent_competence_uid')->with('parentCompetence')->whereNull('parent_competence_uid')->orderBy('name', 'ASC');
    }

    public function allSubcompetences()
    {
        return $this->hasMany(CompetencesModel::class, 'parent_competence_uid', 'uid')
            ->orderBy('created_at', 'ASC')
            ->with(['allSubcompetences' => function ($query) {

                $query->select('uid', 'name', 'parent_competence_uid', 'description')
                      ->with(['learningResults' => function ($query) {
                          $query->select('uid', 'name', 'competence_uid', 'description');
                      }])->orderBy('created_at', 'ASC');
            }]);
    }

    public function learningResults()
    {
        return $this->hasMany(LearningResultsModel::class, 'competence_uid', 'uid');
    }
}
