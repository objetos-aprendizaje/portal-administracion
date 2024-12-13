<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EducationalProgramsAssessmentsModel extends Model {

    use HasFactory;
    protected $table = 'educational_programs_assessments';

    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = [
        'user_uid', 'calification', 'educational_program_uid'
    ];

}
