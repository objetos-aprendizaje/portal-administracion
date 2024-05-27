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
            $table->foreign(['category_uid'], 'erc_category_fk')->references(['uid'])->on('categories')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['educational_resource_uid'], 'erc_educational_resource_fk')->references(['uid'])->on('educational_resources')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('educational_resource_categories', function (Blueprint $table) {
            $table->dropForeign('erc_category_fk');
            $table->dropForeign('erc_educational_resource_fk');
        });
    }
};
