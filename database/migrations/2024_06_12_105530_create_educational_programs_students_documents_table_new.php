<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEducationalProgramsStudentsDocumentsTableNew extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('educational_programs_students_documents', function (Blueprint $table) {
            $table->uuid('uid', 36)->primary();
            $table->uuid('user_uid', 36);
            $table->uuid('educational_program_document_uid', 36);
            $table->text('document_path');
            $table->timestamps();

            $table->foreign('user_uid', 'epsd_user_uid_fk')->references('uid')->on('users')->onDelete('cascade');
            $table->foreign('educational_program_document_uid', 'epsd_epd_uid_fk')->references('uid')->on('educational_programs_documents')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('educational_programs_students_documents');
    }
}
