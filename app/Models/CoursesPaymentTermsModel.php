<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoursesPaymentTermsModel extends Model
{
    use HasFactory;
    protected $table = 'courses_payment_terms';

    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $casts = [
        'uid' => 'string',
    ];

    public $incrementing = false;

    protected $fillable = ['uid', 'course_uid', 'name', 'start_date', 'finish_date', 'cost'];

    public $timestamps = false;

}
