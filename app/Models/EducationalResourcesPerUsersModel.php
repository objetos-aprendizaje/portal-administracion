<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalResourcesPerUsersModel extends Model
{
    use HasFactory;

    protected $table = 'educational_resource_access';

    protected $primaryKey = 'uid';

}
