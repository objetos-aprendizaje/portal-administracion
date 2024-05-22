<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogsModel extends Model
{
    use HasFactory;
    protected $table = 'logs';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $casts = [
        'uid' => 'string',
    ];
    public $incrementing = false;

    protected $fillable = ['uid', 'info', 'entity', 'user_uid', 'created_at'];

    protected $dates = ['created_at'];

    public $timestamps = false;

    public function user()
    {
        return $this->hasOne(
            UsersModel::class,
            'uid',
            'user_uid'
        );
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });
    }
}
