<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalResourceCategoriesModel extends Model
{
    use HasFactory;
    protected $table = 'educational_resource_categories';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

}
