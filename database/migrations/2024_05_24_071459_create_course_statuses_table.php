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
        Schema::create('course_statuses', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('name', 100);
            $table->string('code', 30);
            $table->timestamps();
        });


        $course_statuses = [
            [
                "uid" => generate_uuid(),
                "name" => "En introducción",
                "code" => "INTRODUCTION"
            ],
            [
                "uid" => generate_uuid(),
                "name" => "Pendiente de aprobación",
                "code" => "PENDING_APPROVAL"
            ],
            [
                "uid" => generate_uuid(),
                "name" => "Aceptado",
                "code" => "ACCEPTED"
            ],
            [
                "uid" => generate_uuid(),
                "name" => "Rechazado",
                "code" => "REJECTED"
            ],
            [
                "uid" => generate_uuid(),
                "name" => "En subsanación para aprobación",
                "code" => "UNDER_CORRECTION_APPROVAL"
            ],
            [
                "uid" => generate_uuid(),
                "name" => "Pendiente de publicación",
                "code" => "PENDING_PUBLICATION"
            ],
            [
                "uid" => generate_uuid(),
                "name" => "Aceptado para publicación",
                "code" => "ACCEPTED_PUBLICATION"
            ],
            [
                "uid" => generate_uuid(),
                "name" => "En subsanación para publicación",
                "code" => "UNDER_CORRECTION_PUBLICATION"
            ],
            [
                "uid" => generate_uuid(),
                "name" => "En inscripción",
                "code" => "INSCRIPTION"
            ],
            [
                "uid" => generate_uuid(),
                "name" => "Pendiente de inscripción",
                "code" => "PENDING_INSCRIPTION"
            ],
            [
                "uid" => generate_uuid(),
                "name" => "En desarrollo",
                "code" => "DEVELOPMENT"
            ],
            [
                "uid" => generate_uuid(),
                "name" => "Finalizado",
                "code" => "FINISHED"
            ],
            [
                'uid' => generate_uuid(),
                'name' => 'Retirado',
                'code' => 'RETIRED',
            ]
        ];

        DB::table('course_statuses')->insert($course_statuses);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_statuses');
    }
};
