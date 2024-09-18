<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetenceFrameworksLevelsModel extends Model
{
    use HasFactory;
    protected $table = 'competence_frameworks_levels';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = ['uid', 'competence_uid', 'name'];

}
