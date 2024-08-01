<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHeaderPageUidAndOrderToHeaderPagesTable extends Migration
{
    public function up()
    {
        Schema::table('header_pages', function (Blueprint $table) {
            $table->uuid('header_page_uid')->nullable()->after('uid');
            $table->integer('order')->unsigned()->after('header_page_uid');

            $table->foreign('header_page_uid')->references('uid')->on('header_pages')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('header_pages', function (Blueprint $table) {
            // Eliminar la clave foránea antes de la columna
            $table->dropForeign(['header_page_uid']);

            $table->dropColumn('header_page_uid');
            $table->dropColumn('order');
        });
    }
}
