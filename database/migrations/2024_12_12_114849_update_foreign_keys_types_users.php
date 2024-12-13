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
        Schema::table('course_global_califications', function (Blueprint $table) {
            $table->dropForeign(['user_uid']);

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('course_learning_result_califications', function (Blueprint $table) {
            $table->dropForeign(['user_uid']);

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('courses_accesses', function (Blueprint $table) {
            $table->dropForeign(['user_uid']);

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('courses_blocks_learning_results_califications', function (Blueprint $table) {
            $table->dropForeign(['user_uid']);

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('courses_payment_terms_users', function (Blueprint $table) {
            $table->dropForeign('cptu_user_uid_fk');

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('courses_payments', function (Blueprint $table) {
            $table->dropForeign(['user_uid']);

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('educational_programs_assessments', function (Blueprint $table) {
            $table->dropForeign(['user_uid']);

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('educational_programs_payment_terms_users', function (Blueprint $table) {
            $table->dropForeign('euptu_user_uid_fk');

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('educational_programs_payments', function (Blueprint $table) {
            $table->dropForeign('ep_payments_user_uid_fk');

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('educational_resources_assessments', function (Blueprint $table) {
            $table->dropForeign(['user_uid']);

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('issued_educational_credentials', function (Blueprint $table) {
            $table->dropForeign(['user_uid']);

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('user_general_notifications', function (Blueprint $table) {
            $table->dropForeign(['user_uid']);

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('user_lanes', function (Blueprint $table) {
            $table->dropForeign(['user_uid']);

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('automatic_resource_approval_users', function (Blueprint $table) {
            $table->dropForeign('qvkei_automatic_resource_approval_users_ibfk_1');

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('courses_assessments', function (Blueprint $table) {
            $table->dropForeign(['user_uid']);

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('courses_students', function (Blueprint $table) {
            $table->dropForeign(['user_uid']);

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('courses_students_documents', function (Blueprint $table) {
            $table->dropForeign(['user_uid']);

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('courses_teachers', function (Blueprint $table) {
            $table->dropForeign(['user_uid']);

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('courses_visits', function (Blueprint $table) {
            $table->dropForeign('usr_cour_vis_user_uid_fk');

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('destinations_email_notifications_users', function (Blueprint $table) {
            $table->dropForeign('user_fk');

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('destinations_general_notifications_users', function (Blueprint $table) {
            $table->dropForeign('user_uid_fk');

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('educational_programs_students', function (Blueprint $table) {
            $table->dropForeign('user_uid_fk_educational_programs_students');

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('educational_programs_students_documents', function (Blueprint $table) {
            $table->dropForeign('epsd_user_uid_fk');

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('general_notifications_automatic_users', function (Blueprint $table) {
            $table->dropForeign(['user_uid']);

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('notifications_changes_statuses_courses', function (Blueprint $table) {
            $table->dropForeign('ncsc_user_fk');

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('reset_password_tokens', function (Blueprint $table) {
            $table->dropForeign('reset_password_tokens_uid_user_foreign');

            $table->foreign('uid_user')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('user_automatic_general_notification_types_disabled', function (Blueprint $table) {
            $table->dropForeign('auto_gene_notif_type_user_uid_fk');

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('user_categories', function (Blueprint $table) {
            $table->dropForeign(['user_uid']);

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('user_email_automatic_notification_types_disabled', function (Blueprint $table) {
            $table->dropForeign('auto_notif_type_user_uid_fk');

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('user_email_notification_types_disabled', function (Blueprint $table) {
            $table->dropForeign('user_email_notif_user_uid_fk');

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('user_general_notification_types_disabled', function (Blueprint $table) {
            $table->dropForeign('user_notification_types_preferences_user_uid_foreign');

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('user_learning_results_preferences', function (Blueprint $table) {
            $table->dropForeign('lr_uid_fk');

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('user_policies_accepted', function (Blueprint $table) {
            $table->dropForeign('usr_pol_acep_user_uid_fk');

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('user_role_relationships', function (Blueprint $table) {
            $table->dropForeign(['user_uid']);

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('course_global_califications', function (Blueprint $table) {
            $table->dropForeign(['user_uid']);

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['creator_user_uid']);

            $table->foreign('creator_user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('educational_programs', function (Blueprint $table) {
            $table->dropForeign(['creator_user_uid']);

            $table->foreign('creator_user_uid')
                ->references('uid')
                ->on('users')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
