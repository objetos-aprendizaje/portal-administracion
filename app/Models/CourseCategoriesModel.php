<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseCategoriesModel extends Model
{
    use HasFactory;
    protected $table = 'course_categories';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

}
