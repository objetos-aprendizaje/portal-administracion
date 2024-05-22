<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallsModel extends Model
{
    use HasFactory;
    protected $table = 'calls';
    protected $primaryKey = 'uid';

    protected $fillable = [
        'name', 'description', 'attachment_path', 'start_date', 'end_date'
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'uid' => 'string',
    ];

    public function educationalProgramTypes()
    {
        return $this->belongsToMany(
            EducationalProgramTypesModel::class,
            'calls_educational_program_types',
            'call_uid',
            'educational_program_type_uid'
        );
    }
}
