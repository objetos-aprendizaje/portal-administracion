<?php
use App\Models\AutomaticNotificationTypesModel;
use App\Models\AutomaticNotificationTypesRolesRelationshipModel;
use App\Models\UserRolesModel;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Obtener todos los tipos de notificaciones automáticas agrupados por código
        $automaticNotificationTypes = AutomaticNotificationTypesModel::all()->groupBy("code");

        // Obtener todos los roles agrupados por código
        $userRoles = UserRolesModel::all()->groupBy("code");

        $relationships = [
            [
                "automatic_notification_type_uid" => $automaticNotificationTypes['NEW_COURSES_NOTIFICATIONS']->first()->uid,
                "user_role_uid" => $userRoles['STUDENT']->first()->uid
            ],
            [
                "automatic_notification_type_uid" => $automaticNotificationTypes['NEW_EDUCATIONAL_RESOURCES_NOTIFICATIONS_MANAGEMENTS']->first()->uid,
                "user_role_uid" => $userRoles['MANAGEMENT']->first()->uid
            ],
            [
                "automatic_notification_type_uid" => $automaticNotificationTypes['CHANGE_STATUS_COURSE']->first()->uid,
                "user_role_uid" => $userRoles['TEACHER']->first()->uid
            ],
            [
                "automatic_notification_type_uid" => $automaticNotificationTypes['CHANGE_STATUS_COURSE']->first()->uid,
                "user_role_uid" => $userRoles['MANAGEMENT']->first()->uid
            ],
            [
                "automatic_notification_type_uid" => $automaticNotificationTypes['NEW_COURSES_NOTIFICATIONS_MANAGEMENTS']->first()->uid,
                "user_role_uid" => $userRoles['MANAGEMENT']->first()->uid
            ],
            [
                "automatic_notification_type_uid" => $automaticNotificationTypes['COURSE_ENROLLMENT_COMMUNICATIONS']->first()->uid,
                "user_role_uid" => $userRoles['STUDENT']->first()->uid
            ],
            [
                "automatic_notification_type_uid" => $automaticNotificationTypes['NEW_EDUCATIONAL_PROGRAMS']->first()->uid,
                "user_role_uid" => $userRoles['STUDENT']->first()->uid
            ],
            [
                "automatic_notification_type_uid" => $automaticNotificationTypes['CHANGE_STATUS_EDUCATIONAL_PROGRAM']->first()->uid,
                "user_role_uid" => $userRoles['TEACHER']->first()->uid
            ],
            [
                "automatic_notification_type_uid" => $automaticNotificationTypes['CHANGE_STATUS_EDUCATIONAL_PROGRAM']->first()->uid,
                "user_role_uid" => $userRoles['MANAGEMENT']->first()->uid
            ],
            [
                "automatic_notification_type_uid" => $automaticNotificationTypes['NEW_EDUCATIONAL_PROGRAMS_NOTIFICATIONS_MANAGEMENTS']->first()->uid,
                "user_role_uid" => $userRoles['MANAGEMENT']->first()->uid
            ],
            [
                "automatic_notification_type_uid" => $automaticNotificationTypes['EDUCATIONAL_PROGRAMS_ENROLLMENT_COMMUNICATIONS']->first()->uid,
                "user_role_uid" => $userRoles['STUDENT']->first()->uid
            ]
        ];

        foreach ($relationships as $relationship) {
            AutomaticNotificationTypesRolesRelationshipModel::create($relationship);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Aquí puedes agregar lógica para revertir los cambios si es necesario
    }
};
