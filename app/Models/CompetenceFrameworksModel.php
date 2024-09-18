<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetenceFrameworksModel extends Model
{
    use HasFactory;
    protected $table = 'competence_frameworks';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = ['uid', 'has_levels', 'name', 'description', 'origin_code', 'created_at', 'updated_at'];


    public function levels() {
        return $this->hasMany(CompetenceFrameworksLevelsModel::class, 'competence_framework_uid', 'uid');
    }

    public function competences() {
        return $this->hasMany(CompetencesModel::class, 'competence_framework_uid', 'uid');
    }

    public function allSubcompetences() {
        return $this->hasMany(CompetencesModel::class, 'competence_framework_uid', 'uid');
    }
}
