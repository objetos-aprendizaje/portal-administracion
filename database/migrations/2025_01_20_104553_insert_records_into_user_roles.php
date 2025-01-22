<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $userRoles = [
            [
                "name" => "Administrador",
                "code" => "ADMINISTRATOR"
            ],
            [
                "name" => "Gestor",
                "code" => "MANAGEMENT"
            ],
            [
                "name" => "Docente",
                "code" => "TEACHER"
            ],
            [
                "name" => "Estudiante",
                "code" => "STUDENT"
            ],
        ];

        foreach ($userRoles as $userRole) {
            DB::table('user_roles')->insert([
                'uid' => generateUuid(),
                'name' => $userRole['name'],
                'code' => $userRole['code'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
