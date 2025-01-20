<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalProgramsModel extends Model
{
    use HasFactory;
    protected $table = 'educational_programs';
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    protected $fillable = [
        'uid', 'name', 'description', 'educational_program_type_uid', 'call_uid',
        'inscription_start_date', 'inscription_finish_date', 'image_path',
        'enrolling_start_date', 'enrolling_finish_date', 'min_required_students', 'validate_student_registrations',
        'evaluation_criteria', 'cost', 'featured_slider', 'featured_slider_title', 'featured_slider_description',
        'featured_slider_color_font', 'featured_slider_image_path', 'featured_main_carrousel',
        'educational_program_status_uid', 'realization_start_date', 'realization_finish_date', 'payment_mode', 'certidigital_credential_uid'
    ];

    public $incrementing = false;

    protected $casts = [
        'uid' => 'string',
    ];

    public function status()
    {
        return $this->belongsTo(EducationalProgramStatusesModel::class, 'educational_program_status_uid', 'uid');
    }

    public function educationalProgramType()
    {
        return $this->belongsTo(EducationalProgramTypesModel::class, 'educational_program_type_uid', 'uid');
    }

    public function courses()
    {
        return $this->hasMany(CoursesModel::class, 'educational_program_uid', 'uid');
    }

    public function tags()
    {
        return $this->hasMany(
            EducationalProgramTagsModel::class,
            'educational_program_uid',
            'uid'
        );
    }

    public function categories()
    {
        return $this->belongsToMany(
            CategoriesModel::class,
            'educationals_programs_categories',
            'educational_program_uid',
            'category_uid'
        );
    }

    public function EducationalProgramDocuments()
    {
        return $this->hasMany(EducationalProgramsDocumentsModel::class, 'educational_program_uid', 'uid');
    }

    public function updateDocuments($documentsArray)
    {
        $existingUids = array_filter(array_column($documentsArray, 'uid'));
        $this->EducationalProgramDocuments()->whereNotIn('uid', $existingUids)->delete();

        foreach ($documentsArray as $document) {
            if ($document['uid']) {
                EducationalProgramsDocumentsModel::where('uid', $document['uid'])
                    ->update([
                        'document_name' => $document['document_name'],
                    ]);
            } else {
                $this->EducationalProgramDocuments()->create([
                    'uid' => generateUuid(),
                    'educational_program_uid' => $this->uid,
                    'document_name' => $document['document_name'],
                ]);
            }
        }
    }

    public function deleteDocuments() {
        $this->EducationalProgramDocuments()->delete();
    }

    public function students()
    {
        return $this->belongsToMany(
            UsersModel::class,
            'educational_programs_students',
            'educational_program_uid',
            'user_uid'
        )->withPivot(['acceptance_status', 'status', 'uid', 'emissions_block_uuid', 'credential_sent', 'credential_sealed'])->as('educational_program_student_info');
    }

    public function student_documents()
    {
        return $this->belongsToMany(
            EducationalProgramsDocumentsModel::class,
            'educational_programs_students_documents',
            'educational_program_document_uid',
            'uid',
            'uid',
            'educational_program_uid'
        )->withPivot('user_uid', 'document_path');
    }
    public function contact_emails() {
        return $this->hasMany(
            EducationalProgramEmailContactsModel::class,
            'educational_program_uid',
            'uid'
        );
    }

    public function creatorUser()
    {
        return $this->belongsTo(UsersModel::class, 'creator_user_uid', 'uid');
    }

    public function paymentTerms() {
        return $this->hasMany(
            EducationalProgramsPaymentTermsModel::class,
            'educational_program_uid',
            'uid'
        );
    }

    public function certidigitalCredential()
    {
        return $this->belongsTo(CertidigitalCredentialsModel::class, 'certidigital_credential_uid', 'uid');
    }
}
