<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('competences', function (Blueprint $table) {
        $table->text('description')->change();
    });
}

public function down()
{
    Schema::table('competences', function (Blueprint $table) {
        $table->string('description', 1000)->change();
    });
}
};
