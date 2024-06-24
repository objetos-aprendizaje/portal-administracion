<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalProgramTagsModel extends Model
{
    use HasFactory;
    protected $table = 'educational_program_tags';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

}
