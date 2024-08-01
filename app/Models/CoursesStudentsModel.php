<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoursesStudentsModel extends Model
{
    use HasFactory;
    protected $table = 'courses_students';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    public function course() {
        return $this->belongsTo(CoursesModel::class, 'course_uid', 'uid');
    }

    public function user() {
        return $this->belongsTo(UsersModel::class, 'user_uid', 'uid');
    }
}
