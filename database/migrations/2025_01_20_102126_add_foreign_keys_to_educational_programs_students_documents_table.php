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
        Schema::table('educational_programs_students_documents', function (Blueprint $table) {
            $table->foreign(['user_uid'])->references(['uid'])->on('users')->onUpdate('no action')->onDelete('restrict');
            $table->foreign(['educational_program_document_uid'], 'epsd_epd_uid_fk')->references(['uid'])->on('educational_programs_documents')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('educational_programs_students_documents', function (Blueprint $table) {
            $table->dropForeign('educational_programs_students_documents_user_uid_foreign');
            $table->dropForeign('epsd_epd_uid_fk');
        });
    }
};
