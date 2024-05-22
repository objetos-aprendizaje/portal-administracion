<?php

namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CoursesBigCarrouselsApprovalsModel extends Authenticatable
{
    use HasFactory;
    protected $table = 'courses_big_carrousels_approvals';
    protected $primaryKey = 'uid';
    protected $fillable = ['uid', 'course_uid'];

    protected $casts = [
        'uid' => 'string',
    ];
}
