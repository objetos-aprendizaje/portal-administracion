<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Saml2TenantsModel extends Model
{
    use HasFactory;

    protected $table = 'saml2_tenants';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    protected $fillable = [
        "uuid",
        "key",
        "idp_entity_id",
        "idp_login_url",
        "idp_logout_url",
        "idp_x509_cert",
        "metadata",
        "relay_state_url",
        "name_id_format"
    ];

    protected $casts = [
        'uuid' => 'string',
    ];

    public $incrementing = false;
}
