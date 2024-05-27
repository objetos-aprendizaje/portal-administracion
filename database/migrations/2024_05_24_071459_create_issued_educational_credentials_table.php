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
        Schema::create('issued_educational_credentials', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('user_uid', 36)->index('qvkei_issued_educational_credentials_user_uid_foreign');
            $table->string('course_uid', 36)->index('qvkei_issued_educational_credentials_course_uid_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issued_educational_credentials');
    }
};
