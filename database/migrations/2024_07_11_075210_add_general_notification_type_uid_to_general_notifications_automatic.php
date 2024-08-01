<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('general_notifications_automatic', function (Blueprint $table) {

            DB::table('general_notifications_automatic')->delete();

            $table->string("automatic_notification_type_uid", 36);
            $table->foreign(['automatic_notification_type_uid'], 'fk_aut_not_typ_uid_gen_notf_autom')->references(['uid'])->on('automatic_notification_types')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_notifications_automatic', function (Blueprint $table) {
            //
        });
    }
};
