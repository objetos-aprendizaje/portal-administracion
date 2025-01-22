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
        Schema::table('educationals_programs_categories', function (Blueprint $table) {
            $table->foreign(['category_uid'], 'epc_cat_uid_foreign')->references(['uid'])->on('categories')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['educational_program_uid'], 'epc_ep_uid_foreign')->references(['uid'])->on('educational_programs')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('educationals_programs_categories', function (Blueprint $table) {
            $table->dropForeign('epc_cat_uid_foreign');
            $table->dropForeign('epc_ep_uid_foreign');
        });
    }
};
