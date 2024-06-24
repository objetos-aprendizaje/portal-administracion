<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToEducationalProgramsStudentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('educational_programs_students', function (Blueprint $table) {
            // Cambia 'ep_uid_fk' y 'user_uid_fk' por nombres únicos si es necesario
            $table->foreign('educational_program_uid', 'ep_uid_fk_educational_programs_students')
                  ->references('uid')->on('educational_programs')
                  ->onDelete('cascade');

            $table->foreign('user_uid', 'user_uid_fk_educational_programs_students')
                  ->references('uid')->on('users')
                  ->onDelete('cascade');

            // Asegúrate de que el nombre de la restricción unique también sea único
            $table->unique(['educational_program_uid', 'user_uid'], 'ep_user_unique_educational_programs_students');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('educational_programs_students', function (Blueprint $table) {
            // Elimina la restricción unique y las claves foráneas en el método down
            $table->dropUnique('ep_user_unique');
            $table->dropForeign('ep_uid_fk');
            $table->dropForeign('user_uid_fk');
        });
    }
}
