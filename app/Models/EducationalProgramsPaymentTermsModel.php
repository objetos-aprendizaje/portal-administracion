<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalProgramsPaymentTermsModel extends Model
{
    use HasFactory;
    protected $table = 'educational_programs_payment_terms';

    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $casts = [
        'uid' => 'string',
    ];

    public $incrementing = false;

    protected $fillable = ['uid', 'educational_program_uid', 'name', 'start_date', 'finish_date', 'cost'];

    public $timestamps = false;

}
