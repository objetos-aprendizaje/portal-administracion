<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicenseTypesModel extends Model
{
    use HasFactory;

    // Definición de la tabla asociada
    protected $table = 'license_types';

    // Definición de la clave primaria
    protected $primaryKey = 'uid';
    protected $keyType = 'string';
    public $incrementing = false; // Si los UID no son auto-incrementales

    // Campos asignables en la base de datos
    protected $fillable = ['name'];

}
