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
        $educationalProgramStatuses = [
            [
                "name" => "En subsanación para aprobación",
                "code" => "UNDER_CORRECTION_APPROVAL"
            ],
            [
                "name" => "En desarrollo",
                "code" => "DEVELOPMENT"
            ],
            [
                "name" => "Pendiente de publicación",
                "code" => "PENDING_PUBLICATION"
            ],
            [
                "name" => "Pendiente de aprobación",
                "code" => "PENDING_APPROVAL"
            ],
            [
                "name" => "En introducción",
                "code" => "INTRODUCTION"
            ],
            [
                "name" => "En subsanación para publicación",
                "code" => "UNDER_CORRECTION_PUBLICATION"
            ],
            [
                "name" => "Retirado",
                "code" => "RETIRED"
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
                "name" => "Aceptado para publicación",
                "code" => "ACCEPTED_PUBLICATION"
            ],
            [
                "name" => "Finalizado",
                "code" => "FINISHED"
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
                "name" => "En matriculación",
                "code" => "ENROLLING"
            ],
            [
                "name" => "Pendiente de decisión",
                "code" => "PENDING_DECISION"
            ]
        ];

        foreach($educationalProgramStatuses as $educationalProgram) {
            DB::table('educational_program_statuses')->insert([
                [
                    'uid' => generateUuid(),
                    'name' => $educationalProgram['name'],
                    'code' => $educationalProgram['code'],
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
