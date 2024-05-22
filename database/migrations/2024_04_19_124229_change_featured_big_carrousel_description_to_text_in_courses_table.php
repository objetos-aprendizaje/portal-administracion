<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ChangeFeaturedBigCarrouselDescriptionToTextInCoursesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('courses')->update(['featured_big_carrousel_description' => '']);

        Schema::table('courses', function (Blueprint $table) {
            $table->text('featured_big_carrousel_description')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('featured_big_carrousel_description')->change();
        });
    }
}
