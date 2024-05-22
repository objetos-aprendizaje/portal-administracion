<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class CoursesStudentsDocumentsModel extends Authenticatable
{
    use HasFactory;
    protected $table = 'courses_students_documents';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $casts = [
        'uid' => 'string',
    ];

    public $incrementing = false;

    public function courseDocument()
    {
        return $this->belongsTo(CourseDocumentsModel::class, 'course_document_uid', 'uid');
    }
}
