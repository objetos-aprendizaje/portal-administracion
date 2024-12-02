<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
        'uid',
        'course_lms_uid',
        'title',
        'description',
        'contact_information',
        'course_type_uid',
        'educational_program_type_uid',
        'call_uid',
        'course_status_uid',
        'min_required_students',
        'center_uid',
        'featured_slider_color_font',
        'inscription_start_date',
        'inscription_finish_date',
        'realization_start_date',
        'realization_finish_date',
        'presentation_video_url',
        'objectives',
        'ects_workload',
        'educational_program_uid',
        'validate_student_registrations',
        'lms_url',
        'lms_system_uid',
        'cost',
        'featured_big_carrousel',
        'featured_big_carrousel_title',
        'featured_big_carrousel_description',
        'featured_small_carrousel',
        'structure',
        'calification_type',
        'belongs_to_educational_program',
        'enrolling_start_date',
        'enrolling_finish_date',
        'evaluation_criteria',
        'payment_mode',
        'creator_user_uid',
        'identifier',
        'certification_type_uid',
        'course_lms_id',
        'certidigital_credential_uid'
    ];


    public function call()
    {
        return $this->belongsTo(CallsModel::class, 'call_uid', 'uid');
    }

    public function course_type()
    {
        return $this->belongsTo(CourseTypesModel::class, 'course_type_uid', 'uid');
    }

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
        )->withPivot('type', 'emissions_block_uuid', 'emissions_block_id', 'credential_sent');
    }

    public function students()
    {
        return $this->belongsToMany(
            UsersModel::class,
            'courses_students',
            'course_uid',
            'user_uid'
        )->withPivot(['acceptance_status', 'status', 'emissions_block_uuid', 'credential_sealed', 'credential_sent', 'uid'])->as('course_student_info');
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

    public function deleteDocuments()
    {
        $this->courseDocuments()->delete();
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

    public function contact_emails()
    {
        return $this->hasMany(
            CoursesEmailsContactsModel::class,
            'course_uid',
            'uid'
        );
    }

    public function center()
    {
        return $this->belongsTo(
            CentersModel::class,
            'center_uid',
            'uid'
        );
    }

    public function educational_program()
    {
        return $this->belongsTo(
            EducationalProgramsModel::class,
            'educational_program_uid',
            'uid'
        );
    }

    public function educational_program_type()
    {
        return $this->belongsTo(
            EducationalProgramTypesModel::class,
            'educational_program_type_uid',
            'uid'
        );
    }

    public function student_documents()
    {
        return $this->belongsToMany(
            CourseDocumentsModel::class,
            'courses_students_documents',
            'course_document_uid',
            'uid',
            'uid',
            'course_uid'
        )->withPivot('user_uid', 'document_path');
    }

    public function teachers_no_coordinate()
    {
        return $this->belongsToMany(
            UsersModel::class,
            'courses_teachers',
            'course_uid',
            'user_uid'
        )->wherePivot('type', '<>', 'coordinator');
    }

    public function teachers_coordinate()
    {
        return $this->belongsToMany(
            UsersModel::class,
            'courses_teachers',
            'course_uid',
            'user_uid'
        )->wherePivot('type', '=', 'coordinator');
    }

    public function lmsSystem()
    {
        return $this->belongsTo(
            LmsSystemsModel::class,
            'lms_system_uid',
            'uid'
        );
    }

    public function paymentTerms()
    {
        return $this->hasMany(
            CoursesPaymentTermsModel::class,
            'course_uid',
            'uid'
        )->orderBy('start_date', 'asc');
    }

    public function accesses()
    {
        return $this->hasMany(CoursesAccesesModel::class, 'course_uid', 'uid');
    }
    public function visits()
    {
        return $this->hasMany(CoursesVisitsModel::class, 'course_uid', 'uid');
    }

    public function embeddings() {
        return $this->hasOne(CoursesEmbeddingsModel::class, 'course_uid', 'uid');
    }

    public function certidigitalCredential()
    {
        return $this->belongsTo(CertidigitalCredentialsModel::class, 'certidigital_credential_uid', 'uid');
    }

    public function certidigitalTeacherCredential() {
        return $this->belongsTo(CertidigitalCredentialsModel::class, 'certidigital_teacher_credential_uid', 'uid');
    }

    public function certidigitalAssesments() {
        return $this->hasMany(CertidigitalAssesmentsModel::class, 'course_uid', 'uid');
    }
}
