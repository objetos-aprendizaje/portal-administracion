<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationalResourcesTagsModel extends Model
{
    protected $table = 'educational_resources_tags';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $keyType = 'string';
}
