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
        Schema::create('general_notifications_automatic_users', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('general_notifications_automatic_uid', 36);
            $table->string('user_uid', 36);
            $table->boolean('is_read')->default(false);

            $table->timestamps();

            $table->foreign('general_notifications_automatic_uid', 'gnau_uid_foreign')
                ->references('uid')
                ->on('general_notifications_automatic')
                ->onDelete('cascade');

            $table->foreign('user_uid')->references('uid')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_notifications_automatic_users');
    }
};
