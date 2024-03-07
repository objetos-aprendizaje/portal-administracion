<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseStatusesModel extends Model
{
    use HasFactory;
    protected $table = 'course_statuses';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

}
