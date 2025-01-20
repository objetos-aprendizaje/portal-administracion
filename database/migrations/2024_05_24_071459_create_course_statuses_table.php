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
            $table->uuid('uid', 36)->primary();
            $table->string('name', 100);
            $table->string('code', 30);
            $table->timestamps();
        });


        $course_statuses = [
            [
                "uid" => generateUuid(),
                "name" => "En introducción",
                "code" => "INTRODUCTION"
            ],
            [
                "uid" => generateUuid(),
                "name" => "Pendiente de aprobación",
                "code" => "PENDING_APPROVAL"
            ],
            [
                "uid" => generateUuid(),
                "name" => "Aceptado",
                "code" => "ACCEPTED"
            ],
            [
                "uid" => generateUuid(),
                "name" => "Rechazado",
                "code" => "REJECTED"
            ],
            [
                "uid" => generateUuid(),
                "name" => "En subsanación para aprobación",
                "code" => "UNDER_CORRECTION_APPROVAL"
            ],
            [
                "uid" => generateUuid(),
                "name" => "Pendiente de publicación",
                "code" => "PENDING_PUBLICATION"
            ],
            [
                "uid" => generateUuid(),
                "name" => "Aceptado para publicación",
                "code" => "ACCEPTED_PUBLICATION"
            ],
            [
                "uid" => generateUuid(),
                "name" => "En subsanación para publicación",
                "code" => "UNDER_CORRECTION_PUBLICATION"
            ],
            [
                "uid" => generateUuid(),
                "name" => "En inscripción",
                "code" => "INSCRIPTION"
            ],
            [
                "uid" => generateUuid(),
                "name" => "Pendiente de inscripción",
                "code" => "PENDING_INSCRIPTION"
            ],
            [
                "uid" => generateUuid(),
                "name" => "En desarrollo",
                "code" => "DEVELOPMENT"
            ],
            [
                "uid" => generateUuid(),
                "name" => "Finalizado",
                "code" => "FINISHED"
            ],
            [
                'uid' => generateUuid(),
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
