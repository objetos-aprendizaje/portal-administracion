<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApprovedToCoursesStudentsTable extends Migration
{
    public function up()
    {
        Schema::table('courses_students', function (Blueprint $table) {
            $table->boolean('approved')->nullable()->default(null);
        });
    }
    public function down()
    {
        Schema::table('courses_students', function (Blueprint $table) {
            $table->dropColumn('approved');
        });
    }
}
