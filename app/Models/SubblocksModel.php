<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubblocksModel extends Model
{
    use HasFactory;

    protected $table = 'course_subblocks';

    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = [
        "uid",
        "block_uid",
        "name",
        "description",
        "order"
    ];

    protected $casts = [
        'uid' => 'string',
    ];

    public $incrementing = false;

    public function elements()
    {
        return $this->hasMany(ElementsModel::class, 'subblock_uid', 'uid');
    }
}
