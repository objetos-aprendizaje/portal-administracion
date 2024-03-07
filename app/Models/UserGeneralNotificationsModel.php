<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGeneralNotificationsModel extends Model
{

    use HasFactory;
    protected $table = 'user_general_notifications';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    public function user()
    {
        return $this->belongsTo(UsersModel::class, 'user_uid', 'uid');
    }

}
