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
        Schema::table('header_pages', function (Blueprint $table) {
            $table->foreign(['header_page_uid'])->references(['uid'])->on('header_pages')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('header_pages', function (Blueprint $table) {
            $table->dropForeign('header_pages_header_page_uid_foreign');
        });
    }
};
