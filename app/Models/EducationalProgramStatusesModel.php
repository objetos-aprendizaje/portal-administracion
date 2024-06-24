<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalProgramStatusesModel extends Model
{
    use HasFactory;
    protected $table = 'educational_program_statuses';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

}
