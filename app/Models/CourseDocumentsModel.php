<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseDocumentsModel extends Model
{
    use HasFactory;
    protected $table = 'course_documents';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = [
        "uid", "course_uid", "document_name"
    ];

}
