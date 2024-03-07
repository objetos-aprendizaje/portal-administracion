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

}
