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
        $educationalResourceStatuses = [
            [
                "name" => "En introducción",
                "code" => "INTRODUCTION"
            ],
            [
                "name" => "Pendiente de aprobación",
                "code" => "PENDING_APPROVAL"
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
                "name" => "Publicado",
                "code" => "PUBLISHED"
            ],
            [
                "name" => "Retirado",
                "code" => "RETIRED"
            ]
        ];

        foreach($educationalResourceStatuses as $educationalResourceStatus) {
            DB::table('educational_resource_statuses')->insert([
                [
                    'uid' => generateUuid(),
                    'name' => $educationalResourceStatus['name'],
                    'code' => $educationalResourceStatus['code'],
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
