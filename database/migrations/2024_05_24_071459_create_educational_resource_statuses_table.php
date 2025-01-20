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
        Schema::create('educational_resource_statuses', function (Blueprint $table) {
            $table->uuid('uid', 36)->primary();
            $table->string('name');
            $table->string('code');
            $table->timestamps();
        });

        $resource_statuses = [
            [
                "uid" => generateUuid(),
                "name" => "En introducción",
                "code" => "INTRODUCTION"
            ],
            [
                "uid" => generateUuid(),
                "name" => "Pendiente de aprobación",
                "code" => "PENDING_APPROVAL"
            ],            [
                "uid" => generateUuid(),
                "name" => "Rechazado",
                "code" => "REJECTED"
            ],            [
                "uid" => generateUuid(),
                "name" => "En subsanación para aprobación",
                "code" => "UNDER_CORRECTION_APPROVAL"
            ],            [
                "uid" => generateUuid(),
                "name" => "Publicado",
                "code" => "PUBLISHED"
            ],            [
                "uid" => generateUuid(),
                "name" => "Retirado",
                "code" => "RETIRED"
            ]
        ];

        DB::table('educational_resource_statuses')->insert($resource_statuses);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educational_resource_statuses');
    }
};
