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
        Schema::table('educational_programs', function (Blueprint $table) {
            $table->foreign(['educational_program_origin_uid'], 'edu_prog_self_ref_fk')->references(['uid'])->on('educational_programs')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['educational_program_status_uid'], 'edu_prog_status_fk')->references(['uid'])->on('educational_program_statuses')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['certidigital_credential_uid'])->references(['uid'])->on('certidigital_credentials')->onUpdate('cascade')->onDelete('set null');
            $table->foreign(['creator_user_uid'])->references(['uid'])->on('users')->onUpdate('no action')->onDelete('restrict');
            $table->foreign(['educational_program_type_uid'], 'qvkei_educational_programs_ibfk_1')->references(['uid'])->on('educational_program_types')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('educational_programs', function (Blueprint $table) {
            $table->dropForeign('edu_prog_self_ref_fk');
            $table->dropForeign('edu_prog_status_fk');
            $table->dropForeign('educational_programs_certidigital_credential_uid_foreign');
            $table->dropForeign('educational_programs_creator_user_uid_foreign');
            $table->dropForeign('qvkei_educational_programs_ibfk_1');
        });
    }
};
