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
        Schema::create('lanes', function (Blueprint $table) {
            $table->uuid('uid', 36)->primary();
            $table->boolean('active');
            $table->timestamps();
            $table->string('code');
        });

        $records = [
            ['uid' => generate_uuid(), 'active' => 1, 'code' => 'FEATURED_COURSES'],
            ['uid' => generate_uuid(), 'active' => 1, 'code' => 'FEATURED_PROGRAMS'],
            ['uid' => generate_uuid(), 'active' => 1, 'code' => 'FEATURED_RESOURCES'],

            ['uid' => generate_uuid(), 'active' => 1, 'code' => 'RECENTS_COURSES'],
            ['uid' => generate_uuid(), 'active' => 1, 'code' => 'RECENTS_PROGRAMS'],
            ['uid' => generate_uuid(), 'active' => 1, 'code' => 'RECENTS_RESOURCES'],
        ];

        DB::table('lanes')->insert($records);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lanes');
    }
};
