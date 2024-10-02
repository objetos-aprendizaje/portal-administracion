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
            $table->uuid('uid', 36)->primary();
            $table->uuid('footer_page_uid', 36);

            $table->foreign('footer_page_uid', 'usr_pol_acep_foot_pag_uid_fk')
                ->references('uid')->on('footer_pages')
                ->onDelete('cascade');
            $table->uuid("user_uid", 36);

            $table->foreign('user_uid', 'usr_pol_acep_user_uid_fk')
                ->references('uid')->on('users')
                ->onDelete('cascade');

            $table->decimal("version", 8, 2)->default(1.0);
            $table->timestamp('accepted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_user_policies_accepted');
    }
};
