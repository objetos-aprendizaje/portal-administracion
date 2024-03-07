<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubelementsModel extends Model
{
    use HasFactory;

    protected $table = 'course_subelements';

    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = [
        "uid",
        "element_uid",
        "name",
        "description",
        "order"
    ];

    protected $casts = [
        'uid' => 'string',
    ];

    public $incrementing = false;
}
