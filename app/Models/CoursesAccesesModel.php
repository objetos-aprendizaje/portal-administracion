<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoursesAccesesModel extends Model
{
    use HasFactory;

    // Definición de la tabla asociada
    protected $table = 'courses_accesses';

    // Definición de la clave primaria
    protected $primaryKey = 'uid';
    protected $keyType = 'string';
    public $incrementing = false; // Si los UID no son auto-incrementales
    public $timestamps = false;

    // Campos asignables en la base de datos
    protected $fillable = ['uid', 'course_uid', 'user_uid', 'access_date'];

}
