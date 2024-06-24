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
        Schema::create('user_automatic_general_notification_types_disabled', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('user_uid', 36);
            $table->string('automatic_notification_type_uid', 36);

            $table->foreign('user_uid', 'auto_gene_notif_type_user_uid_fk')
                ->references('uid')->on('users')
                ->onDelete('cascade');

            $table->foreign('automatic_notification_type_uid', 'auto_gene_notif_type_uid_fk')
                ->references('uid')->on('automatic_notification_types')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_automatic_general_notification_types_disabled');
    }
};
