<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalResourcesEmbeddingsModel extends Model
{
    use HasFactory;
    protected $table = 'educational_resources_embeddings';
    protected $primaryKey = 'educational_resource_uid';

    protected $keyType = 'string';

    protected $casts = [
        'educational_resource_uid' => 'string',
        'embeddings' => 'array'
    ];

    public $incrementing = false;

    protected $fillable = [
        'educational_resource_uid',
        'embeddings',
    ];

}
