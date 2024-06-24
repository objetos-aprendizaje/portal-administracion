<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterCoursesStudentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('courses_students', function (Blueprint $table) {
            $table->dropColumn('approved');
            $table->enum('acceptance_status', ['PENDING', 'ACCEPTED', 'REJECTED'])->default('PENDING');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('courses_students', function (Blueprint $table) {
            $table->boolean('approved')->default(false);
            $table->dropColumn('acceptance_status');
        });
    }
}
