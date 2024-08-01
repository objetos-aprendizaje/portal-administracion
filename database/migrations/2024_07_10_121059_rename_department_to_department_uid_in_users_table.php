<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameDepartmentToDepartmentUidInUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('department_uid')->nullable(); // Agregar nueva columna
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('department'); // Eliminar columna antigua
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('department')->nullable(); // Agregar columna antigua
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('department_uid'); // Eliminar nueva columna
        });
    }
}
