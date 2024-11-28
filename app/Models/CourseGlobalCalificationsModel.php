<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseGlobalCalificationsModel extends Model
{
    use HasFactory;
    protected $table = 'course_global_califications';

    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = [
        "uid",
        "course_uid",
        "user_uid",
        "calification_info"
    ];
}
