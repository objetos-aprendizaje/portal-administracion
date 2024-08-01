<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalResourcesLearningResultsModel extends Model
{
    use HasFactory;
    protected $table = 'educational_resources_learning_results';
    protected $primaryKey = 'uid';

    protected $fillable = [
        'educational_resource_uid', 'learning_result_uid'
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'uid' => 'string',
    ];

}
