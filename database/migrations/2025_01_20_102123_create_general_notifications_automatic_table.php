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
        Schema::create('general_notifications_automatic', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->text('title');
            $table->text('description');
            $table->string('entity')->nullable();
            $table->uuid('entity_uid')->nullable();
            $table->timestamps();
            $table->uuid('automatic_notification_type_uid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_notifications_automatic');
    }
};
