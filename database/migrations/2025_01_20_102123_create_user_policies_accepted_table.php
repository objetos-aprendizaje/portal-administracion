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
        Schema::create('user_policies_accepted', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->uuid('footer_page_uid');
            $table->uuid('user_uid');
            $table->decimal('version')->default(1);
            $table->timestamp('accepted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_policies_accepted');
    }
};
