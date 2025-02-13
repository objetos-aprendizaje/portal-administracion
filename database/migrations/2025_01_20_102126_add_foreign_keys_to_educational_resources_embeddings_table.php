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
        Schema::table('educational_resources_embeddings', function (Blueprint $table) {
            $table->foreign(['educational_resource_uid'], 'educational_resources_embeddings_educational_resource_uid_fkey')->references(['uid'])->on('educational_resources')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('educational_resources_embeddings', function (Blueprint $table) {
            $table->dropForeign('educational_resources_embeddings_educational_resource_uid_fkey');
        });
    }
};
