<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class EmailNotificationsAutomaticModel extends Model
{
    use HasFactory;
    protected $table = 'email_notifications_automatic';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = ['uid', 'subject', 'body', 'sent', 'created_at', 'updated_at', 'user_uid'];

    protected $casts = [
        'uid' => 'string',
    ];

    public function user() {
        return $this->belongsTo(UsersModel::class, 'user_uid', 'uid');
    }
}
