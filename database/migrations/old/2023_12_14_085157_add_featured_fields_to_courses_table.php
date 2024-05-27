<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFeaturedFieldsToCoursesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('featured_big_carrousel')->default(false);
            $table->string('featured_big_carrousel_title', 255)->nullable();
            $table->string('featured_big_carrousel_description', 255)->nullable();
            $table->string('featured_big_carrousel_image_path', 255)->nullable();
            $table->boolean('featured_small_carrousel')->default(false);
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
            $table->dropColumn([
                'featured_big_carrousel',
                'featured_big_carrousel_title',
                'featured_big_carrousel_description',
                'featured_big_carrousel_image_path',
                'featured_small_carrousel'
            ]);
        });
    }
}
