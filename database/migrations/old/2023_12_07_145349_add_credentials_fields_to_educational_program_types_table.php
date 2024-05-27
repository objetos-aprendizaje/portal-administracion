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
        Schema::table('educational_program_types', function (Blueprint $table) {
            $table->boolean('managers_can_emit_credentials')->default(false);
            $table->boolean('teachers_can_emit_credentials')->default(false);
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('educational_program_types', function (Blueprint $table) {
            $table->dropColumn('managers_can_emit_credentials');
            $table->dropColumn('teachers_can_emit_credentials');
        });
    }

};
