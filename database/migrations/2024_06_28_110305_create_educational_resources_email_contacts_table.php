<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEducationalResourcesEmailContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('educational_resources_email_contacts', function (Blueprint $table) {
            $table->uuid('uid', 36)->primary();
            $table->uuid('educational_resource_uid', 36);
            $table->string('email', 255);

            // Define la clave foránea
            $table->foreign('educational_resource_uid', 'edu_res_email_contact_fk')
                ->references('uid')->on('educational_resources')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('educational_resources_email_contacts');
    }
}
