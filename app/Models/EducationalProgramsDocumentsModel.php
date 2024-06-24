<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalProgramsDocumentsModel extends Model
{
    use HasFactory;
    protected $table = 'educational_programs_documents';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = [
        "uid", "educational_program_uid", "document_name"
    ];

    public function educationalProgramsStudentsDocuments() {
        return $this->hasMany(EducationalProgramsStudentsDocumentsModel::class, 'educational_program_document_uid', 'uid');
    }

    public function educationalProgramStudentDocument() {
        return $this->hasOne(EducationalProgramsStudentsDocumentsModel::class, 'educational_program_document_uid', 'uid');
    }

}
