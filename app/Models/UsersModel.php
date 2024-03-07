<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UsersModel extends Authenticatable
{
    use HasFactory;
    protected $table = 'users';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = ['first_name', 'last_name', 'nif', 'email', 'user_rol_uid', 'curriculum'];

    public function rol()
    {
        return $this->belongsTo(UserRolesModel::class, 'user_rol_uid', 'uid');
    }


    public function roles()
    {
        return $this->belongsToMany(UserRolesModel::class, 'user_role_relationships', 'user_uid', 'user_role_uid')
            ->withPivot('uid', 'created_at', 'updated_at')
            ->withTimestamps();
    }

    public function coursesStudents()
    {
        return $this->belongsToMany(
            CoursesModel::class,
            'courses_students',
            'user_uid',
            'course_uid'
        )->withPivot('calification_type', 'approved', 'credential')->select(['title']);
    }

    public function coursesTeachers()
    {
        return $this->belongsToMany(
            CoursesModel::class,
            'courses_teachers',
            'user_uid',
            'course_uid'
        )->withPivot('credential')->select(['title']);
    }

    public function courseStudentDocuments()
    {
        return $this->hasManyThrough(
            CoursesStudentDocumentsModel::class,
            CoursesStudentsModel::class,
            'user_uid',
            'courses_students_uid',
            'uid',
            'uid'
        )->select(['courses_students_documents.*', 'course_documents.document_name'])
            ->join('course_documents', 'courses_students_documents.course_documents_uid', '=', 'course_documents.uid');
    }

    public function notificationsTypesPreferences()
    {
        return $this->belongsToMany(
            NotificationsTypesModel::class,
            'user_notification_types_preferences',
            'user_uid',
            'notification_type_uid'
        );
    }

    public function hasAnyRole(array $roles)
    {
        return !empty(array_intersect($roles, array_column($this->roles->toArray(), 'code')));
    }
}
