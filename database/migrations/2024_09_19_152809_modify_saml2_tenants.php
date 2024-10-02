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
        Schema::table('saml2_tenants', function (Blueprint $table) {
            $table->string('idp_entity_id', 255)->nullable()->change();
            $table->string('idp_login_url', 255)->nullable()->change();
            $table->string('idp_logout_url', 255)->nullable()->change();
            $table->text('idp_x509_cert')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
