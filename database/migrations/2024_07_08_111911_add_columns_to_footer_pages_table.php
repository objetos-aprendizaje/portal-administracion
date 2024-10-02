<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('footer_pages', function (Blueprint $table) {
            // Añadir el campo 'slug'
            $table->string('slug')->nullable();

            // Añadir el campo 'order'
            $table->integer('order')->default(0);

            // Añadir el campo 'footer_page_uid'
            $table->uuid('footer_page_uid', 36)->nullable();
        });
    }

    public function down()
    {
        Schema::table('footer_pages', function (Blueprint $table) {
            // Revertir los cambios (opcionalmente)
            $table->dropColumn('slug');
            $table->dropColumn('order');
            $table->dropColumn('footer_page_uid');
        });
    }
};
