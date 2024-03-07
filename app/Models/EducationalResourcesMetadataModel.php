<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalResourcesMetadataModel extends Model
{
    use HasFactory;

    protected $table = 'educational_resources_metadata';

    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        "uid", "educational_resources_uid", "metadata_key", "metadata_value"
    ];


}
