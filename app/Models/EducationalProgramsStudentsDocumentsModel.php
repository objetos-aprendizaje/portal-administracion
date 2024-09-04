<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalProgramsStudentsDocumentsModel extends Model
{
    use HasFactory;
    protected $table = 'educational_programs_students_documents';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $casts = [
        'uid' => 'string',
    ];

    protected $fillable = ['uid', 'user_uid', 'educational_program_document_uid' ,'document_path'];



    public $incrementing = false;

    public function educationalProgramDocument()
    {
        return $this->belongsTo(EducationalProgramsDocumentsModel::class, 'educational_program_document_uid', 'uid');
    }
}
