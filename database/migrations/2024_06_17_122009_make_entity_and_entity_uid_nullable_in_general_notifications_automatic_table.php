<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeEntityAndEntityUidNullableInGeneralNotificationsAutomaticTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('general_notifications_automatic', function (Blueprint $table) {
            $table->string('entity')->nullable()->change();
            $table->uuid('entity_uid', 36)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('general_notifications_automatic', function (Blueprint $table) {
            $table->string('entity')->nullable(false)->change();
            $table->uuid('entity_uid', 36)->nullable(false)->change();
        });
    }
}
