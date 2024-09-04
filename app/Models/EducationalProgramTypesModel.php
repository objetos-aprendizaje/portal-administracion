<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalProgramTypesModel extends Model
{
    use HasFactory;
    protected $table = 'educational_program_types';
    protected $primaryKey = 'uid';
    
    public $incrementing = false; // El uid no debe ser auto-incremental    

    protected $keyType = 'string';

    protected $fillable = [
        'uid', 'name', 'description', 'managers_can_emit_credentials', 'teachers_can_emit_credentials'
    ];

}
