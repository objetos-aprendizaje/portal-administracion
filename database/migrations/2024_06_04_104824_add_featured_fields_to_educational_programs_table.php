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
        Schema::table('educational_programs', function (Blueprint $table) {
            $table->boolean('featured_slider')->default(false);
            $table->string('featured_slider_title')->nullable();
            $table->text('featured_slider_description')->nullable();
            $table->string('featured_slider_color_font')->nullable();
            $table->string('featured_slider_image_path')->nullable();
            $table->boolean('featured_main_carrousel')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('educational_programs', function (Blueprint $table) {
            $table->dropColumn([
                'featured_slider',
                'featured_slider_title',
                'featured_slider_description',
                'featured_slider_color_font',
                'featured_slider_image_path',
                'featured_main_carrousel',
            ]);
        });
    }
};
