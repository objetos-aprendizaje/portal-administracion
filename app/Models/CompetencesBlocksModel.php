<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetencesBlocksModel extends Model
{
    use HasFactory;
    protected $table = 'competences_blocks';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

}
