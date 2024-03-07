<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalResourceTypesModel extends Model
{
    use HasFactory;

    protected $table = 'educational_resource_types';

    protected $primaryKey = 'uid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'uid',
        'name',
        'description'    ];

    public function resources()
    {
        return $this->hasMany(EducationalResource::class, 'educational_resource_type_uid', 'uid');
    }
}
