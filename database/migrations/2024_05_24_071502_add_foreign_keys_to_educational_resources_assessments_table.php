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
        Schema::table('educational_resources_assessments', function (Blueprint $table) {
            $table->foreign(['educational_resources_uid'], 'edu_res_fk')->references(['uid'])->on('educational_resources')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['user_uid'])->references(['uid'])->on('users')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('educational_resources_assessments', function (Blueprint $table) {
            $table->dropForeign('edu_res_fk');
            $table->dropForeign('qvkei_educational_resources_assessments_user_uid_foreign');
        });
    }
};
