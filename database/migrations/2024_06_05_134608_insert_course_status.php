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
        DB::table('course_statuses')->insert([
            'uid' => generate_uuid(),
            'name' => 'Matriculación',
            'code' => 'ENROLLING',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('course_statuses')
            ->where('name', 'Matriculación')
            ->where('code', 'Enrolling')
            ->delete();
    }
};
