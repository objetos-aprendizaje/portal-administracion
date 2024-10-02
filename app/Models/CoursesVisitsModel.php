<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoursesVisitsModel extends Model
{
    use HasFactory;

    protected $table = 'courses_visits';

    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['uid', 'course_uid', 'user_uid', 'access_date'];
}
