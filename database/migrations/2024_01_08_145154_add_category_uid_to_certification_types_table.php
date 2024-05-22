<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCategoryUidToCertificationTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('certification_types', function (Blueprint $table) {
            $table->string('category_uid', 36)->nullable();

            $table->foreign('category_uid')
                  ->references('uid')
                  ->on('categories')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('certification_types', function (Blueprint $table) {
            $table->dropForeign(['category_uid']);
            $table->dropColumn('category_uid');
        });
    }
}
