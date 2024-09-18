<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalResourcesAccesesModel extends Model
{
    use HasFactory;

    // Definición de la tabla asociada
    protected $table = 'educational_resource_access';

    // Definición de la clave primaria
    protected $primaryKey = 'uid';
    protected $keyType = 'string';
    public $incrementing = false; // Si los UID no son auto-incrementales

    // Campos asignables en la base de datos
    protected $fillable = ['uid', 'educational_resource_uid', 'user_uid', 'date'];

}
