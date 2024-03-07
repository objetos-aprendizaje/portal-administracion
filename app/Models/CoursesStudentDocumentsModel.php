<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoursesStudentDocumentsModel extends Model
{
    use HasFactory;
    protected $table = 'courses_students_documents';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

}
