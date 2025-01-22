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
        Schema::table('educational_programs_documents', function (Blueprint $table) {
            $table->foreign(['educational_program_uid'], 'epd_ep_uid_foreign')->references(['uid'])->on('educational_programs')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('educational_programs_documents', function (Blueprint $table) {
            $table->dropForeign('epd_ep_uid_foreign');
        });
    }
};
