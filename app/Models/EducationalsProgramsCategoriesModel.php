<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalsProgramsCategoriesModel extends Model
{
    use HasFactory;
    protected $table = 'educationals_programs_categories';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

}
