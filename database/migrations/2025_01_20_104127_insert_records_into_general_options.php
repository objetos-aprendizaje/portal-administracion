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
        // insertar registros en la tabla general_options

        $fieldsToInsert = [
            "university_name",
            "legal_advice",
            "necessary_approval_resources",
            "necessary_approval_editions",
            "color_1",
            "color_2",
            "color_3",
            "color_4",
            "scripts",
            "carrousel_image_path",
            "carrousel_title",
            "carrousel_description",
            "truetype_regular_file_path",
            "woff_regular_file_path",
            "woff2_regular_file_path",
            "embedded_opentype_regular_file_path",
            "opentype_regular_input_file",
            "svg_regular_file_path",
            "truetype_medium_file_path",
            "woff_medium_file_path",
            "woff2_medium_file_path",
            "embedded_opentype_medium_file_path",
            "opentype_medium_file_path",
            "svg_medium_file_path",
            "truetype_bold_file_path",
            "woff_bold_file_path",
            "woff2_bold_file_path",
            "embedded_opentype_bold_file_path",
            "opentype_bold_file_path",
            "svg_bold_file_path",
            "google_login_active",
            "google_client_id",
            "google_client_secret",
            "facebook_login_active",
            "facebook_client_id",
            "facebook_client_secret",
            "linkedin_login_active",
            "linkedin_client_id",
            "linkedin_client_secret",
            "twitter_login_active",
            "redsys_enabled",
            "smtp_server",
            "main_slider_color_font",
            "cas_active",
            "rediris_active",
            "twitter_client_id",
            "poa_logo_2",
            "poa_logo_3",
            "threshold_abandoned_courses",
            "managers_can_manage_categories",
            "managers_can_manage_course_types",
            "managers_can_manage_educational_resources_types",
            "managers_can_manage_calls",
            "twitter_client_secret",
            "enabled_recommendation_module",
            "smtp_port",
            "smtp_user",
            "smtp_password",
            "smtp_address_from",
            "smtp_encryption",
            "smtp_name_from",
            "redsys_url",
            "payment_gateway",
            "redsys_commerce_code",
            "redsys_terminal",
            "redsys_currency",
            "redsys_transaction_type",
            "redsys_encryption_key",
            "lane_featured_educationals_programs",
            "openai_key",
            "instagram_url",
            "telegram_url",
            "lane_recents_educational_resources",
            "lane_featured_itineraries",
            "footer_text_1",
            "footer_text_2",
            "learning_objects_appraisals",
            "operation_by_calls",
            "poa_logo_1",
            "linkedin_url",
            "lane_featured_courses",
            "certidigital_client_secret",
            "registration_active",
            "certidigital_username",
            "certidigital_password",
            "certidigital_url_token",
            "certidigital_center_id",
            "certidigital_organization_oid",
            "certidigital_url",
            "facebook_url",
            "x_url",
            "youtube_url",
            "certidigital_client_id",
            "company_name",
            "phone_number",
            "cif",
            "fiscal_domicile",
        ];

        foreach($fieldsToInsert as $field) {
            DB::table('general_options')->insert([
                'option_name' => $field,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
