<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElementsModel extends Model
{
    use HasFactory;

    protected $table = 'course_elements';

    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = [
        "uid",
        "subblock_uid",
        "name",
        "description",
        "order"
    ];

    protected $casts = [
        'uid' => 'string',
    ];

    public $incrementing = false;

    public function subElements()
    {
        return $this->hasMany(SubelementsModel::class, 'element_uid', 'uid');
    }
}
