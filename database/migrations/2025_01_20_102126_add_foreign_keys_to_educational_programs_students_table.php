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
        Schema::table('educational_programs_students', function (Blueprint $table) {
            $table->foreign(['user_uid'])->references(['uid'])->on('users')->onUpdate('no action')->onDelete('restrict');
            $table->foreign(['educational_program_uid'], 'ep_uid_fk_educational_programs_students')->references(['uid'])->on('educational_programs')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('educational_programs_students', function (Blueprint $table) {
            $table->dropForeign('educational_programs_students_user_uid_foreign');
            $table->dropForeign('ep_uid_fk_educational_programs_students');
        });
    }
};
