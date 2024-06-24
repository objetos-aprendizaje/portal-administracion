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
        Schema::create('educational_program_statuses', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->string('name', 100);
            $table->string('code', 30);
            $table->timestamps();
        });

        DB::table('educational_program_statuses')->insert([
            ['uid' => '0b9c4173-098c-43c5-9b09-cba017869c42', 'name' => 'En subsanación para aprobación', 'code' => 'UNDER_CORRECTION_APPROVAL'],
            ['uid' => '5114e6b2-1282-4594-8cec-451fca9d6005', 'name' => 'En desarrollo', 'code' => 'DEVELOPMENT'],
            ['uid' => '53266fce-4745-488d-baa8-23a043835ef1', 'name' => 'Pendiente de publicación', 'code' => 'PENDING_PUBLICATION'],
            ['uid' => '55ff9a0c-64d0-4d1f-933c-e9f9468fbb9d', 'name' => 'Pendiente de aprobación', 'code' => 'PENDING_APPROVAL'],
            ['uid' => '61037db5-ab99-4c6e-951e-403341eca5fc', 'name' => 'En introducción', 'code' => 'INTRODUCTION'],
            ['uid' => '63d3f5ff-a093-495c-8570-08a689eb9404', 'name' => 'En subsanación para publicación', 'code' => 'UNDER_CORRECTION_PUBLICATION'],
            ['uid' => '6e56445b-215d-49df-a4ef-83ffc0f195b9', 'name' => 'Retirado', 'code' => 'RETIRED'],
            ['uid' => '97624cc9-c924-4976-8482-72d73f64b0f7', 'name' => 'Aceptado', 'code' => 'ACCEPTED'],
            ['uid' => '9d4eac91-cd10-4ecf-ad42-94562ad3a4cd', 'name' => 'Rechazado', 'code' => 'REJECTED'],
            ['uid' => 'aae9f28e-37d9-4916-8c00-ed24238d8ac6', 'name' => 'Aceptado para publicación', 'code' => 'ACCEPTED_PUBLICATION'],
            ['uid' => 'b78a9d6a-ab78-4708-8d25-6fa3d5a9f642', 'name' => 'Finalizado', 'code' => 'FINISHED'],
            ['uid' => 'b8c2be00-2972-4042-bfb2-98ef3f189ad3', 'name' => 'En inscripción', 'code' => 'INSCRIPTION'],
            ['uid' => 'd5b172c6-d107-4a4e-b37c-4261d336dd83', 'name' => 'Pendiente de inscripción', 'code' => 'PENDING_INSCRIPTION'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educational_program_statuses');
    }
};
