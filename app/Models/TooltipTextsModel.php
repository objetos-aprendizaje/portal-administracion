<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TooltipTextsModel extends Model
{

    use HasFactory;
    protected $table = 'tooltip_texts';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = [
        "uid",
        "input_id",
        "description",
        "form_id"
    ];
}
