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
        Schema::table('educational_resources', function (Blueprint $table) {
            $table->foreign(['status_uid'], 'qvkei_educational_resources_ibfk_2')->references(['uid'])->on('educational_resource_statuses')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('educational_resources', function (Blueprint $table) {
            $table->dropForeign('qvkei_educational_resources_ibfk_2');
        });
    }
};
