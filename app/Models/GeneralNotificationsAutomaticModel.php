<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class GeneralNotificationsAutomaticModel extends Model
{
    use HasFactory;

    protected $table = 'general_notifications_automatic';

    protected $primaryKey = 'uid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'uid',
        'title',
        'description',
        'entity',
        'entity_uid'
    ];

}
