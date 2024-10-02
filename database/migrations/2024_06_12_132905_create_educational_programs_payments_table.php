<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEducationalProgramsPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('educational_programs_payments', function (Blueprint $table) {
            $table->uuid('uid', 36)->primary();
            $table->uuid('user_uid', 36);
            $table->uuid('educational_program_uid', 36);
            $table->string('order_number', 12);
            $table->text('info')->nullable();
            $table->tinyInteger('is_paid');
            $table->timestamps();

            // Using unique names for the foreign key constraints
            $table->foreign('user_uid', 'ep_payments_user_uid_fk')->references('uid')->on('users')->onDelete('cascade');
            $table->foreign('educational_program_uid', 'ep_payments_edu_prog_uid_fk')->references('uid')->on('educational_programs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('educational_programs_payments');
    }
}
