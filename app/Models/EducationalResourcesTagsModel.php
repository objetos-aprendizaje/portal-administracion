<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EducationalResourcesTagsModel extends Model
{
    use HasFactory;
    
    protected $table = 'educational_resources_tags';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $keyType = 'string';


}
