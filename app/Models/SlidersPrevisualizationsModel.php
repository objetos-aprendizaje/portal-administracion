<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlidersPrevisualizationsModel extends Model
{
    use HasFactory;
    protected $table = 'sliders_previsualizations';
    protected $primaryKey = 'uid';

    protected $fillable = [
        'title', 'description', 'image_path', 'color'
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'uid' => 'string',
    ];

}
