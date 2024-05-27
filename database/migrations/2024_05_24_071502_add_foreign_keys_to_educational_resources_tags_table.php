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
        Schema::table('educational_resources_tags', function (Blueprint $table) {
            $table->foreign(['educational_resource_uid'], 'qvkei_educational_resources_tags_ibfk_1')->references(['uid'])->on('educational_resources')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('educational_resources_tags', function (Blueprint $table) {
            $table->dropForeign('qvkei_educational_resources_tags_ibfk_1');
        });
    }
};
