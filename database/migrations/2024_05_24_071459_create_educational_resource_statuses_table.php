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
                "uid" => generate_uuid(),
                "name" => "En introducción",
                "code" => "INTRODUCTION"
            ],
            [
                "uid" => generate_uuid(),
                "name" => "Pendiente de aprobación",
                "code" => "PENDING_APPROVAL"
            ],            [
                "uid" => generate_uuid(),
                "name" => "Rechazado",
                "code" => "REJECTED"
            ],            [
                "uid" => generate_uuid(),
                "name" => "En subsanación para aprobación",
                "code" => "UNDER_CORRECTION_APPROVAL"
            ],            [
                "uid" => generate_uuid(),
                "name" => "Publicado",
                "code" => "PUBLISHED"
            ],            [
                "uid" => generate_uuid(),
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
