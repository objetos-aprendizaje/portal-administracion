<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalProgramsStudentsModel extends Model
{
    use HasFactory;
    protected $table = 'educational_programs_students';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = ['uid', 'educational_program_uid', 'user_uid', 'acceptance_status', 'emissions_block_id', 'emissions_block_uuid'];


    public function educationalProgram()
    {
        return $this->belongsTo(EducationalProgramsModel::class, 'educational_program_uid', 'uid');
    }
}
