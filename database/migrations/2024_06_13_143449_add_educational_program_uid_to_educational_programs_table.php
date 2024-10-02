<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEducationalProgramUidToEducationalProgramsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('educational_programs', function (Blueprint $table) {
            // Agregar el nuevo campo que puede ser nulo
            $table->uuid('educational_program_origin_uid', 36)->nullable()->after('uid');

            // Use a more unique name for the foreign key constraint
            $table->foreign('educational_program_origin_uid', 'edu_prog_self_ref_fk')->references('uid')->on('educational_programs')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('educational_programs', function (Blueprint $table) {
            // When rolling back, specify the same unique constraint name
            $table->dropForeign('edu_prog_self_ref_fk');
            $table->dropColumn('educational_program_origin_uid');
        });
    }
}
