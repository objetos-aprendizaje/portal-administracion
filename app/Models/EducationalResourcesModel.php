<?php

namespace App\Models;

use CreateEducationalResourcesEmailContactsTable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class EducationalResourcesModel extends Model
{
    use HasFactory;

    protected $table = 'educational_resources';

    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'uid',
        'title',
        'description',
        'image_path',
        'path',
        'tags',
        'metadata',
        'status_uid',
        'educational_resource_type_uid',
        'category_uid',
        'course_uid',
        'license_type',
        'resource_way',
        'resource_url',
        'embeddings'
    ];

    public function status()
    {
        return $this->belongsTo(EducationalResourceStatusesModel::class,  'status_uid', 'uid');
    }

    public function type()
    {
        return $this->belongsTo(EducationalResourceTypesModel::class, 'educational_resource_type_uid', 'uid');
    }

    public function tags()
    {
        return $this->hasMany(EducationalResourcesTagsModel::class, 'educational_resource_uid', 'uid');
    }

    public function metadata()
    {
        return $this->hasMany(EducationalResourcesMetadataModel::class, 'educational_resources_uid', 'uid')->orderBy('updated_at', 'asc');;
    }

    public function updateMetadata($metadataArray)
    {
        $existingUids = array_filter(array_column($metadataArray, 'uid'));
        $this->metadata()->whereNotIn('uid', $existingUids)->delete();

        foreach ($metadataArray as $metadata) {
            if ($metadata['uid']) {
                EducationalResourcesMetadataModel::where('uid', $metadata['uid'])
                    ->update([
                        'metadata_key' => $metadata['metadata_key'],
                        'metadata_value' => $metadata['metadata_value']
                    ]);
            } else {
                $this->metadata()->create([
                    'uid' => generate_uuid(),
                    'metadata_key' => $metadata['metadata_key'],
                    'metadata_value' => $metadata['metadata_value']
                ]);
            }
        }
    }

    public function categories()
    {
        return $this->belongsToMany(
            CategoriesModel::class,
            'educational_resource_categories',
            'educational_resource_uid',
            'category_uid'
        );
    }

    public function creatorUser()
    {
        return $this->belongsTo(UsersModel::class, 'creator_user_uid', 'uid');
    }

    public function contact_emails() {
        return $this->hasMany(
            EducationalResourcesEmailContactsModel::class,
            'educational_resource_uid',
            'uid'
        );
    }
    public function license_type()
    {
        return $this->belongsTo(LicenseTypesModel::class, 'license_type_uid', 'uid');
    }

    public function learningResults()
    {
        return $this->belongsToMany(
            LearningResultsModel::class,
            'educational_resources_learning_results',
            'educational_resource_uid',
            'learning_result_uid'
        );
    }
    public function accesses()
    {
        return $this->hasMany(EducationalResourcesAccesesModel::class, 'educational_resource_uid', 'uid');
    }
    public function visits()
    {
        return $this->hasMany(EducationalResourcesAccesesModel::class, 'educational_resource_uid', 'uid');
    }
}
