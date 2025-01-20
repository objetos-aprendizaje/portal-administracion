<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->uuid('uid', 36)->primary();
            $table->string('name', 50);
            $table->string('code', 50);
            $table->timestamps();
        });

        $user_roles = [
            [
                "uid" => generateUuid(),
                "name" => "Administrador",
                "code" => "ADMINISTRATOR"
            ],
            [
                "uid" => generateUuid(),
                "name" => "Gestor",
                "code" => "MANAGEMENT"
            ],
            [
                "uid" => generateUuid(),
                "name" => "Docente",
                "code" => "TEACHER"
            ],
            [
                "uid" => generateUuid(),
                "name" => "Estudiante",
                "code" => "STUDENT"
            ],
        ];

        DB::table('user_roles')->insert($user_roles);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
