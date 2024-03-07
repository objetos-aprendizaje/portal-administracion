<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationsChangesStatusesCoursesModel extends Model
{
    use HasFactory;
    protected $table = 'notifications_changes_statuses_courses';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = ['user_uid', 'course_uid', 'course_status_uid', 'date'];

    public $timestamps = false;


    public function user() {
        return $this->belongsTo(UsersModel::class, 'user_uid', 'uid');
    }

    public function status() {
        return $this->belongsTo(CourseStatusesModel::class, 'course_status_uid', 'uid');
    }

    public function course() {
        return $this->belongsTo(CoursesModel::class, 'course_uid', 'uid');
    }
}
