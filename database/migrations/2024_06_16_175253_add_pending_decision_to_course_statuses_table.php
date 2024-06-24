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
        DB::table('course_statuses')->insert([
            'uid' => generate_uuid(),
            'name' => 'Pendiente de decisión',
            'code' => 'PENDING_DECISION',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_statuses', function (Blueprint $table) {
            //
        });
    }
};
