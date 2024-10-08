<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoursesTagsModel extends Model
{
    use HasFactory;
    protected $table = 'courses_tags';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = ['uid', 'course_uid', 'tag'];

}
