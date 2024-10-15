<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class UsersModel extends Authenticatable
{
    use HasFactory, Notifiable;
    protected $table = 'users';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $casts = [
        'uid' => 'string',
    ];

    public $incrementing = false;

    protected $fillable = ['uid','first_name', 'last_name', 'nif', 'email', 'user_rol_uid', 'curriculum', 'department_uid'];

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
        )->withPivot('calification_type', 'acceptance_status', 'credential')->select(['title']);
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
        return $this->hasMany(CoursesStudentsDocumentsModel::class, 'user_uid');
    }

    public function educationalProgramDocuments()
    {
        return $this->hasMany(EducationalProgramsStudentsDocumentsModel::class, 'user_uid');
    }

    public function generalNotificationsTypesDisabled()
    {
        return $this->belongsToMany(
            NotificationsTypesModel::class,
            'user_general_notification_types_disabled',
            'user_uid',
            'notification_type_uid'
        );
    }

    public function emailNotificationsTypesDisabled()
    {
        return $this->belongsToMany(
            NotificationsTypesModel::class,
            'user_email_notification_types_disabled',
            'user_uid',
            'notification_type_uid'
        );
    }

    public function automaticGeneralNotificationsTypesDisabled()
    {
        return $this->belongsToMany(
            AutomaticNotificationTypesModel::class,
            'user_automatic_general_notification_types_disabled',
            'user_uid',
            'automatic_notification_type_uid'
        );
    }

    public function automaticEmailNotificationsTypesDisabled()
    {
        return $this->belongsToMany(
            AutomaticNotificationTypesModel::class,
            'user_email_automatic_notification_types_disabled',
            'user_uid',
            'automatic_notification_type_uid'
        );
    }

    public function hasAnyRole(array $roles)
    {
        return !empty(array_intersect($roles, array_column($this->roles->toArray(), 'code')));
    }

    public function hasAnyAutomaticGeneralNotificationTypeDisabled(array $roles)
    {
        return !empty(array_intersect($roles, array_column($this->automaticGeneralNotificationsTypesDisabled->toArray(), 'code')));
    }

    // Relación muchos a muchos con la tabla intermedia user_general_notifications
    public function notifications()
    {
        return $this->belongsToMany(GeneralNotificationsModel::class, 'user_general_notifications', 'user_uid', 'general_notification_uid')
                    ->withPivot('view_date');
    }

    public function categories()
    {
        return $this->belongsToMany(
            CategoriesModel::class,
            'user_categories',
            'user_uid',
            'category_uid'
        );
    }

     // Relación muchos a muchos con la tabla intermedia educational_resource_access
    public function educationalResources()
    {
        return $this->belongsToMany(EducationalResourcesModel::class, 'educational_resource_access', 'user_uid', 'educational_resource_uid')->withPivot('date');;
    }

    public function EducationalProgramsStudents()
    {
        return $this->belongsToMany(
            EducationalProgramsModel::class,
            'educational_programs_students',
            'user_uid',
            'educational_program_uid'
        )->withPivot('calification_type', 'approved', 'credential')->select(['title']);
    }
    public function department()
    {
        return $this->belongsTo(DepartmentsModel::class, 'department_uid');
    }

    public function learningResultsPreferences() {
        return $this->belongsToMany(
            LearningResultsModel::class,
            'user_learning_results_preferences',
            'user_uid',
            'learning_result_uid'
        );
    }

    public function courseBlocksLearningResultsCalifications()
    {
        return $this->hasMany(CoursesBlocksLearningResultsCalificationsModel::class, 'user_uid');
    }

    public function courseLearningResultCalifications()
    {
        return $this->hasMany(CourseLearningResultCalificationsModel::class, 'user_uid');
    }
}
