<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoursesModel extends Model
{
    use HasFactory;
    protected $table = 'courses';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';
    protected $casts = [
        'uid' => 'string',
    ];
    public $incrementing = false;

    protected $fillable = [
        'title', 'description', 'course_type_uid', 'educational_program_type_uid',
        'call_uid', 'course_status_uid', 'min_required_students', 'center',
        'inscription_start_date', 'inscription_finish_date', 'realization_start_date', 'realization_finish_date',
        'presentation_video_url', 'objectives', 'ects_workload', 'educational_program_uid',
        'validate_student_registrations', 'lms_url', 'cost', 'featured_big_carrousel',
        'featured_big_carrousel_title', 'featured_big_carrousel_description', 'featured_small_carrousel', 'structure'
    ];


    public function status()
    {
        return $this->belongsTo(CourseStatusesModel::class, 'course_status_uid', 'uid');
    }

    public function creatorUser()
    {
        return $this->belongsTo(UsersModel::class, 'creator_user_uid', 'uid');
    }

    public function teachers()
    {
        return $this->belongsToMany(
            UsersModel::class,
            'courses_teachers',
            'course_uid',
            'user_uid'
        );
    }

    public function students()
    {
        return $this->belongsToMany(
            UsersModel::class,
            'courses_students',
            'course_uid',
            'user_uid'
        )->withPivot(['approved', 'uid'])->as('course_student_info');
    }

    public function courseDocuments()
    {
        return $this->hasMany(CourseDocumentsModel::class, 'course_uid', 'uid');
    }

    public function tags()
    {
        return $this->hasMany(
            CoursesTagsModel::class,
            'course_uid',
            'uid'
        );
    }

    public function updateDocuments($documentsArray)
    {
        $existingUids = array_filter(array_column($documentsArray, 'uid'));
        $this->courseDocuments()->whereNotIn('uid', $existingUids)->delete();

        foreach ($documentsArray as $document) {
            if ($document['uid']) {
                CourseDocumentsModel::where('uid', $document['uid'])
                    ->update([
                        'document_name' => $document['document_name'],
                    ]);
            } else {
                $this->courseDocuments()->create([
                    'uid' => generate_uuid(),
                    'course_uid' => $this->uid,
                    'document_name' => $document['document_name'],
                ]);
            }
        }
    }

    public function categories()
    {
        return $this->belongsToMany(
            CategoriesModel::class,
            'course_categories',
            'course_uid',
            'category_uid'
        );
    }

    public function blocks()
    {
        return $this->hasMany(
            BlocksModel::class,
            'course_uid',
            'uid'
        );
    }
}
