<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {Schema::table('user_lanes', function (Blueprint $table) {
        $table->foreign('lane_uid')->references('uid')->on('lanes');
        $table->foreign('user_uid')->references('uid')->on('users');
    });

    Schema::table('user_general_notifications', function (Blueprint $table) {
        $table->foreign('user_uid')->references('uid')->on('users');
        $table->foreign('general_notification_uid', 'fk_general_notification_uid')->references('uid')->on('general_notifications');
    });

    Schema::table('user_role_relationships', function (Blueprint $table) {
        $table->foreign('user_uid')->references('uid')->on('users');
        $table->foreign('user_role_uid')->references('uid')->on('user_roles');
    });

    /*Schema::table('course_teachers', function (Blueprint $table) {
        $table->foreign('course_uid')->references('uid')->on('courses');
        $table->foreign('user_uid')->references('uid')->on('users');
    });*/

    Schema::table('user_courses', function (Blueprint $table) {
        $table->foreign('user_uid')->references('uid')->on('users');
        $table->foreign('course_uid')->references('uid')->on('courses');
    });

    Schema::table('issued_educational_credentials', function (Blueprint $table) {
        $table->foreign('user_uid')->references('uid')->on('users');
        $table->foreign('course_uid')->references('uid')->on('courses');
    });

    Schema::table('user_categories', function (Blueprint $table) {
        $table->foreign('user_uid')->references('uid')->on('users');
        $table->foreign('category_uid')->references('uid')->on('categories');
    });

    Schema::table('course_categories', function (Blueprint $table) {
        $table->foreign('course_uid')->references('uid')->on('courses');
        $table->foreign('category_uid')->references('uid')->on('categories');
    });

    Schema::table('categories', function (Blueprint $table) {
        $table->foreign('parent_category_uid')->references('uid')->on('course_categories');
    });

    Schema::table('course_edition_relationships', function (Blueprint $table) {
        $table->foreign('course_uid')->references('uid')->on('courses');
    });

    Schema::table('educational_resource_categories', function (Blueprint $table) {
        $table->foreign('educational_resource_uid')->references('uid')->on('educational_resources')->name('educational_resource_category_fk');
        $table->foreign('category_uid')->references('uid')->on('categories');
    });

    Schema::table('course_contact_emails', function (Blueprint $table) {
        $table->foreign('course_uid')->references('uid')->on('courses');
    });

    Schema::table('course_accesses', function (Blueprint $table) {
        $table->foreign('course_uid')->references('uid')->on('courses');
    });

    Schema::table('user_auto_approval_resources', function (Blueprint $table) {
        $table->foreign('user_uid')->references('uid')->on('users')->name('user_auto_approval_resources_user_uid_fk');
    });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }
};
