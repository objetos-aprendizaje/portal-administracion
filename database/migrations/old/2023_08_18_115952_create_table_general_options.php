<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('general_options', function (Blueprint $table) {
            $table->id();
            $table->string("option_name", 255);
            $table->text("option_value")->nullable();
            $table->timestamps();
        });

        $initial_options = [
            ["option_name" => "university_name"],
            ["option_name" => "poa_logo"],
            ["option_name" => "learning_objects_appraisals"],
            ["option_name" => "payment_gateway"],
            ["option_name" => "operation_by_calls"],
            ["option_name" => "managers_can_manage_categories"],
            ["option_name" => "managers_can_manage_course_types"],
            ["option_name" => "managers_can_manage_educational_resources_types"],
            ["option_name" => "smtp_server"],
            ["option_name" => "smtp_port"],
            ["option_name" => "smtp_user"],
            ["option_name" => "smtp_password"],
            ["option_name" => "color_primary"],
            ["option_name" => "color_secondary"],
            ["option_name" => "company_name"],
            ["option_name" => "commercial_name"],
            ["option_name" => "cif"],
            ["option_name" => "fiscal_domicile"],
            ["option_name" => "work_center_address"],
            ["option_name" => "legal_advice"],
            ["option_name" => "lane_featured_courses"],
            ["option_name" => "lane_featured_educationals_programs"],
            ["option_name" => "lane_recents_educational_resources"],
            ["option_name" => "lane_featured_itineraries"],
            ["option_name" => "necessary_approval_courses"],
            ["option_name" => "necessary_approval_resources"],
            ["option_name" => "course_status_change_notifications"]
        ];

        DB::table('general_options')->insert($initial_options);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_options');
    }
};
