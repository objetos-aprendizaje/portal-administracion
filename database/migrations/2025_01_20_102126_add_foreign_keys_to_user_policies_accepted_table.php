<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_policies_accepted', function (Blueprint $table) {
            $table->foreign(['user_uid'])->references(['uid'])->on('users')->onUpdate('no action')->onDelete('restrict');
            $table->foreign(['footer_page_uid'], 'usr_pol_acep_foot_pag_uid_fk')->references(['uid'])->on('footer_pages')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_policies_accepted', function (Blueprint $table) {
            $table->dropForeign('user_policies_accepted_user_uid_foreign');
            $table->dropForeign('usr_pol_acep_foot_pag_uid_fk');
        });
    }
};
