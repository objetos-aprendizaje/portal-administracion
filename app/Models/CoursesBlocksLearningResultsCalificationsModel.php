<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoursesBlocksLearningResultsCalificationsModel extends Model
{
    use HasFactory;
    protected $table = 'courses_blocks_learning_results_califications';

    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = [
        "uid",
        "user_uid",
        "course_block_uid",
        "learning_result_uid",
        "competence_framework_level_uid",
        "calification_info"
    ];

    public function block()
    {
        return $this->belongsTo(BlocksModel::class, 'course_block_uid', 'uid');
    }
}
