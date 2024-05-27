<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('general_options')->insert([
            ['option_name' => 'university_name', 'option_value' => null],
            ['option_name' => 'poa_logo', 'option_value' => null],
            ['option_name' => 'learning_objects_appraisals', 'option_value' => null],
            ['option_name' => 'payment_gateway', 'option_value' => 0],
            ['option_name' => 'operation_by_calls', 'option_value' => null],
            ['option_name' => 'managers_can_manage_categories', 'option_value' => null],
            ['option_name' => 'managers_can_manage_course_types', 'option_value' => null],
            ['option_name' => 'managers_can_manage_educational_resources_types', 'option_value' => null],
            ['option_name' => 'smtp_server', 'option_value' => null],
            ['option_name' => 'smtp_port', 'option_value' => null],
            ['option_name' => 'smtp_user', 'option_value' => null],
            ['option_name' => 'smtp_password', 'option_value' => null],
            ['option_name' => 'company_name', 'option_value' => null],
            ['option_name' => 'commercial_name', 'option_value' => null],
            ['option_name' => 'cif', 'option_value' => null],
            ['option_name' => 'fiscal_domicile', 'option_value' => null],
            ['option_name' => 'work_center_address', 'option_value' => null],
            ['option_name' => 'legal_advice', 'option_value' => null],
            ['option_name' => 'lane_featured_courses', 'option_value' => null],
            ['option_name' => 'lane_featured_educationals_programs', 'option_value' => null],
            ['option_name' => 'lane_recents_educational_resources', 'option_value' => null],
            ['option_name' => 'lane_featured_itineraries', 'option_value' => null],
            ['option_name' => 'necessary_approval_courses', 'option_value' => null],
            ['option_name' => 'necessary_approval_resources', 'option_value' => null],
            ['option_name' => 'course_status_change_notifications', 'option_value' => null],
            ['option_name' => 'necessary_approval_editions', 'option_value' => null],
            ['option_name' => 'color_1', 'option_value' => '#2C4C7E'],
            ['option_name' => 'color_2', 'option_value' => '#507AB9'],
            ['option_name' => 'color_3', 'option_value' => '#1F1F20'],
            ['option_name' => 'color_4', 'option_value' => '#585859'],
            ['option_name' => 'scripts', 'option_value' => null],
            ['option_name' => 'redsys_commerce_code', 'option_value' => null],
            ['option_name' => 'redsys_terminal', 'option_value' => null],
            ['option_name' => 'redsys_currency', 'option_value' => null],
            ['option_name' => 'redsys_transaction_type', 'option_value' => null],
            ['option_name' => 'redsys_encryption_key', 'option_value' => null],
            ['option_name' => 'facebook_url', 'option_value' => null],
            ['option_name' => 'x_url', 'option_value' => null],
            ['option_name' => 'youtube_url', 'option_value' => null],
            ['option_name' => 'instagram_url', 'option_value' => null],
            ['option_name' => 'telegram_url', 'option_value' => null],
            ['option_name' => 'linkedin_url', 'option_value' => null],
            ['option_name' => 'carrousel_image_path', 'option_value' => null],
            ['option_name' => 'carrousel_title', 'option_value' => null],
            ['option_name' => 'carrousel_description', 'option_value' => null],
            ['option_name' => 'truetype_regular_file_path', 'option_value' => null],
            ['option_name' => 'woff_regular_file_path', 'option_value' => null],
            ['option_name' => 'woff2_regular_file_path', 'option_value' => null],
            ['option_name' => 'embedded_opentype_regular_file_path', 'option_value' => null],
            ['option_name' => 'opentype_regular_input_file', 'option_value' => null],
            ['option_name' => 'svg_regular_file_path', 'option_value' => null],
            ['option_name' => 'truetype_medium_file_path', 'option_value' => null],
            ['option_name' => 'woff_medium_file_path', 'option_value' => null],
            ['option_name' => 'woff2_medium_file_path', 'option_value' => null],
            ['option_name' => 'embedded_opentype_medium_file_path', 'option_value' => null],
            ['option_name' => 'opentype_medium_file_path', 'option_value' => null],
            ['option_name' => 'svg_medium_file_path', 'option_value' => null],
            ['option_name' => 'truetype_bold_file_path', 'option_value' => null],
            ['option_name' => 'woff_bold_file_path', 'option_value' => null],
            ['option_name' => 'woff2_bold_file_path', 'option_value' => null],
            ['option_name' => 'embedded_opentype_bold_file_path', 'option_value' => null],
            ['option_name' => 'opentype_bold_file_path', 'option_value' => null],
            ['option_name' => 'svg_bold_file_path', 'option_value' => null],
            ['option_name' => 'google_login_active', 'option_value' => 0],
            ['option_name' => 'google_client_id', 'option_value' => null],
            ['option_name' => 'google_client_secret', 'option_value' => null],
            ['option_name' => 'facebook_login_active', 'option_value' => 0],
            ['option_name' => 'facebook_client_id', 'option_value' => null],
            ['option_name' => 'facebook_client_secret', 'option_value' => null],
            ['option_name' => 'twitter_login_active', 'option_value' => 0],
            ['option_name' => 'twitter_client_id', 'option_value' => null],
            ['option_name' => 'twitter_client_secret', 'option_value' => null],
            ['option_name' => 'linkedin_login_active', 'option_value' => 0],
            ['option_name' => 'linkedin_client_id', 'option_value' => null],
            ['option_name' => 'linkedin_client_secret', 'option_value' => null],
            ['option_name' => 'smtp_name_from', 'option_value' => null],
            ['option_name' => 'managers_can_manage_calls', 'option_value' => null],
            ['option_name' => 'redsys_enabled', 'option_value' => 0],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
