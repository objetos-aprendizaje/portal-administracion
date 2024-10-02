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
        Schema::create('educationals_programs_categories', function (Blueprint $table) {
            $table->uuid('uid', 36)->primary();
            $table->uuid('educational_program_uid', 36);
            $table->uuid('category_uid', 36);
            $table->timestamps();

            $table->foreign('educational_program_uid', 'epc_ep_uid_foreign')->references('uid')->on('educational_programs')->onDelete('cascade');
            $table->foreign('category_uid', 'epc_cat_uid_foreign')->references('uid')->on('categories')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educationals_programs_categories');
    }
};
