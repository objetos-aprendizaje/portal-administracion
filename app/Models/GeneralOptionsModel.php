<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralOptionsModel extends Model
{
    use HasFactory;
    protected $table = 'general_options';

    protected $fillable = ['option_name','option_value'];
}
