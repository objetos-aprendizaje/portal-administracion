<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreatorUserUidToEducationalResourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('educational_resources', function (Blueprint $table) {
            $table->string('creator_user_uid', 36);
            //$table->foreign('creator_user_uid')->references('uid')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('educational_resources', function (Blueprint $table) {
            $table->dropForeign(['creator_user_uid']);
            $table->dropColumn('creator_user_uid');
        });
    }
}
