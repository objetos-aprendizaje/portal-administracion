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
        Schema::table('educational_resource_categories', function (Blueprint $table) {
            $table->foreign('educational_resource_uid', 'erc_educational_resource_fk')
                ->references('uid')
                ->on('educational_resources')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('category_uid', 'erc_category_fk')
                ->references('uid')
                ->on('categories')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('educational_resource_categories', function (Blueprint $table) {
            //
        });
    }
};
