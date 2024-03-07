<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallsEducationalProgramTypesModel extends Model
{
    use HasFactory;

    protected $table = 'calls_educational_program_types';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = [
        'uid', 'call_uid', 'educational_program_type_uid'
    ];

    public function call() {
        return $this->belongsTo(CallsModel::class, 'call_uid', 'uid');
    }

    public function educationalProgramType() {
        return $this->belongsTo(EducationalProgramTypesModel::class, 'educational_program_type_uid', 'uid');
    }
}
