<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoursesTeachersModel extends Model
{
    use HasFactory;
    protected $table = 'courses_teachers';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = [
        'uid',
        'course_uid',
        'user_uid',
        'created_at',
        'updated_at',
    ];

}
