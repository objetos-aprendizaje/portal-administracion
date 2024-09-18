<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentsModel extends Model
{
    use HasFactory;

    // Definición de la tabla asociada
    protected $table = 'departments';

    // Definición de la clave primaria
    protected $primaryKey = 'uid';
    protected $keyType = 'string';
    public $incrementing = false; // Si los UID no son auto-incrementales

    // Campos asignables en la base de datos
    protected $fillable = ['name', 'uid'];

    // Relación con el modelo de usuarios
    public function users()
    {
        return $this->hasMany(UsersModel::class, 'department_uid');
    }
}
