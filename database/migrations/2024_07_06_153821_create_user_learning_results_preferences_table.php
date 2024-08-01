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
        Schema::create('user_learning_results_preferences', function (Blueprint $table) {
            $table->string('learning_result_uid', 36);
            $table->string('user_uid', 36);

            // Establecer las claves foráneas con nombres explícitos más cortos
            $table->foreign('learning_result_uid', 'lr_uid_fk')
                  ->references('uid')->on('learning_results')
                  ->onDelete('cascade');

            $table->foreign('user_uid', 'lr_u_user_uid_fk')
                  ->references('uid')->on('users')
                  ->onDelete('cascade');

            // Establecer la clave primaria compuesta
            $table->primary(['learning_result_uid', 'user_uid']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_learning_results_preferences', function (Blueprint $table) {
            $table->dropForeign('lr_uid_fk');
            $table->dropForeign('user_uid_fk');
        });

        Schema::dropIfExists('user_learning_results_preferences');
    }
};
