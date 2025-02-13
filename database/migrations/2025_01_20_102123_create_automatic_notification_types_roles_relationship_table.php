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
        Schema::create('automatic_notification_types_roles_relationship', function (Blueprint $table) {
            $table->uuid('automatic_notification_type_uid');
            $table->uuid('user_role_uid');

            $table->primary(['automatic_notification_type_uid', 'user_role_uid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automatic_notification_types_roles_relationship');
    }
};
