<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RedirectionQueriesLearningObjectsModel extends Model
{
    use HasFactory;
    protected $table = 'redirection_queries_learning_objects';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = [
        'educational_program_type_uid', 'course_type_uid', 'type', 'contact', 'learning_object_type'
    ];

    public function educational_program_type()
    {
        return $this->hasOne(EducationalProgramTypesModel::class, 'uid', 'educational_program_type_uid');
    }

    public function course_type()
    {
        return $this->hasOne(CourseTypesModel::class, 'uid', 'course_type_uid');
    }
}
