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
        Schema::table('educational_programs_students', function (Blueprint $table) {
            if (Schema::hasColumn('educational_programs_students', 'calification')) {
                $table->dropColumn('calification');
            }

            if (Schema::hasColumn('educational_programs_students', 'calification_type')) {
                $table->dropColumn('calification_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
