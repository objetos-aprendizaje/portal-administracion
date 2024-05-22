<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalProgramsModel extends Model
{
    use HasFactory;
    protected $table = 'educational_programs';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = [
        'name', 'description', 'educational_program_type_uid', 'call_uid', 'inscription_start_date', 'inscription_finish_date', 'image_path', 'is_modular'
    ];

    public $incrementing = false;

    protected $casts = [
        'uid' => 'string',
    ];

    public function educationalProgramType()
    {
        return $this->belongsTo(EducationalProgramTypesModel::class, 'educational_program_type_uid', 'uid');
    }

    public function courses()
    {
        return $this->hasMany(CoursesModel::class, 'educational_program_uid', 'uid');
    }
}
