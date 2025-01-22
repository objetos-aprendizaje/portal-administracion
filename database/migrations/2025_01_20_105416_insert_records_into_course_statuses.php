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

        $courseStatuses = [
            [
                "name" => "En introducción",
                "code" => "INTRODUCTION"
            ],
            [
                "name" => "Pendiente de aprobación",
                "code" => "PENDING_APPROVAL"
            ],
            [
                "name" => "Aceptado",
                "code" => "ACCEPTED"
            ],
            [
                "name" => "Rechazado",
                "code" => "REJECTED"
            ],
            [
                "name" => "En subsanación para aprobación",
                "code" => "UNDER_CORRECTION_APPROVAL"
            ],
            [
                "name" => "Pendiente de publicación",
                "code" => "PENDING_PUBLICATION"
            ],
            [
                "name" => "Aceptado para publicación",
                "code" => "ACCEPTED_PUBLICATION"
            ],
            [
                "name" => "En subsanación para publicación",
                "code" => "UNDER_CORRECTION_PUBLICATION"
            ],
            [
                "name" => "En inscripción",
                "code" => "INSCRIPTION"
            ],
            [
                "name" => "Pendiente de inscripción",
                "code" => "PENDING_INSCRIPTION"
            ],
            [
                "name" => "En desarrollo",
                "code" => "DEVELOPMENT"
            ],
            [
                "name" => "Finalizado",
                "code" => "FINISHED"
            ],
            [
                "name" => "Retirado",
                "code" => "RETIRED"
            ],
            [
                "name" => "Matriculación",
                "code" => "ENROLLING"
            ],
            [
                "name" => "Listo para añadir a programa formativo",
                "code" => "READY_ADD_EDUCATIONAL_PROGRAM"
            ],
            [
                "name" => "Añadido a programa formativo",
                "code" => "ADDED_EDUCATIONAL_PROGRAM"
            ],
            [
                "name" => "Pendiente de decisión",
                "code" => "PENDING_DECISION"
            ]
        ];

        foreach($courseStatuses as $courseStatus) {
            DB::table('course_statuses')->insert([
                [
                    'uid' => generateUuid(),
                    'name' => $courseStatus['name'],
                    'code' => $courseStatus['code'],
                ]
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
