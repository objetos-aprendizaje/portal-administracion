<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailsSuggestionsModel extends Model
{

    use HasFactory;

    protected $table = 'emails_suggestions';

    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $casts = [
        'uid' => 'string',
    ];

    public $incrementing = false;

    protected $fillable = [
        'uid', 'name', 'message', 'sent'
    ];

}
