<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningResultsBlocksModel extends Model
{
    use HasFactory;
    protected $table = 'learning_results_blocks';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    public $timestamps = false;

}
