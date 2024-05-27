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
        Schema::table('certification_types', function (Blueprint $table) {
            $table->foreign(['category_uid'])->references(['uid'])->on('categories')->onUpdate('set null')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certification_types', function (Blueprint $table) {
            $table->dropForeign('qvkei_certification_types_category_uid_foreign');
        });
    }
};
