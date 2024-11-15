<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseLearningResultCalificationsModel extends Model
{
    use HasFactory;
    protected $table = 'course_learning_result_califications';

    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = [
        "uid",
        "user_uid",
        "course_uid",
        "learning_result_uid",
        "competence_framework_level_uid",
        "calification_info"
    ];
}
