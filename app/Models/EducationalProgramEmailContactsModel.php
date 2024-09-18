<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalProgramEmailContactsModel extends Model
{
    use HasFactory;

    protected $table = 'educational_programs_email_contacts';

    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        "uid", "educational_program_uid", "email"
    ];

    public $timestamps = false;


}
