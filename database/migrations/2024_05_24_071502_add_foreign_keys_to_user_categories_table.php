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
        Schema::table('user_categories', function (Blueprint $table) {
            $table->foreign(['category_uid'])->references(['uid'])->on('categories')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['user_uid'])->references(['uid'])->on('users')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_categories', function (Blueprint $table) {
            $table->dropForeign('qvkei_user_categories_category_uid_foreign');
            $table->dropForeign('qvkei_user_categories_user_uid_foreign');
        });
    }
};
