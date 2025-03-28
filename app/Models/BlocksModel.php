<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlocksModel extends Model
{
    use HasFactory;

    protected $table = 'course_blocks';

    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $casts = [
        'uid' => 'string',
    ];

    public $incrementing = false;


    protected $fillable = [
        "uid",
        "course_uid",
        "name",
        "description",
        "type",
        "order"
    ];

    public function subBlocks()
    {
        return $this->hasMany(SubblocksModel::class, 'block_uid', 'uid');
    }

    public function competences()
    {
        return $this->belongsToMany(
            CompetencesModel::class,
            'competences_blocks',
            'course_block_uid',
            'competence_uid'
        );
    }

    public function learningResults()
    {
        return $this->belongsToMany(
            LearningResultsModel::class,
            'learning_results_blocks',
            'course_block_uid',
            'learning_result_uid'
        );
    }

    public function califications()
    {
        return $this->hasMany(CoursesBlocksLearningResultsCalificationsModel::class, 'course_block_uid', 'uid');
    }
}
