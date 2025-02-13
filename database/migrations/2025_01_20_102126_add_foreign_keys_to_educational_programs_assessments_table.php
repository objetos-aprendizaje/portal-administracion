<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('educational_programs_assessments', function (Blueprint $table) {
            $table->foreign(['educational_program_uid'], 'edu_prog_uid_fk')->references(['uid'])->on('educational_programs')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['user_uid'])->references(['uid'])->on('users')->onUpdate('no action')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('educational_programs_assessments', function (Blueprint $table) {
            $table->dropForeign('edu_prog_uid_fk');
            $table->dropForeign('educational_programs_assessments_user_uid_foreign');
        });
    }
};
