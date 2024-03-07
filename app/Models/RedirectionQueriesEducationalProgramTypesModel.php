<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RedirectionQueriesEducationalProgramTypesModel extends Model
{
    use HasFactory;
    protected $table = 'redirection_queries_educational_program_types';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = [
        'educational_program_type_uid', 'type', 'contact'
    ];

    public function educational_program_type()
    {
        return $this->hasOne(EducationalProgramTypesModel::class, 'uid', 'educational_program_type_uid');
    }
}
