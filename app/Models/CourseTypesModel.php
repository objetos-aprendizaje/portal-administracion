<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseTypesModel extends Model
{
    use HasFactory;
    protected $table = 'course_types';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = ['uid', 'name'];

}
