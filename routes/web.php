<?php

use App\Http\Controllers\Administration\ApiKeysController;
use App\Http\Controllers\Administration\AuthenticationConfigurationController;
use App\Http\Controllers\Administration\CarrouselsController;
use App\Http\Controllers\Administration\CentersController;
use App\Http\Controllers\Administration\FooterPagesController;
use App\Http\Controllers\Administration\GeneralAdministrationController;
use App\Http\Controllers\Administration\HeaderPagesController;
use App\Http\Controllers\Administration\ManagementPermissionsController;
use App\Http\Controllers\Administration\SuggestionsImprovementsController;
use App\Http\Controllers\Administration\RedirectionQueriesEducationalProgramTypesController;
use App\Http\Controllers\Administration\LanesShowController;
use App\Http\Controllers\Administration\LmsSystemsController;
use App\Http\Controllers\Administration\LoginSystemsController;
use App\Http\Controllers\Administration\PaymentsController;
use App\Http\Controllers\Management\ManagementCoursesController;
use App\Http\Controllers\Management\ManagementGeneralConfigurationController;
use App\Http\Controllers\Management\CallsController;
use App\Http\Controllers\Cataloging\CategoriesController;
use App\Http\Controllers\Cataloging\CourseTypesController;
use App\Http\Controllers\Cataloging\EducationalProgramTypesController;
use App\Http\Controllers\Cataloging\EducationalResourceTypesController;
use App\Http\Controllers\LearningObjects\EducationalResourcesPerUsersController;
use App\Http\Controllers\LearningObjects\EducationalProgramsController;
use App\Http\Controllers\Notifications\GeneralNotificationsController;
use App\Http\Controllers\LearningObjects\EducationalResourcesController;
use App\Http\Controllers\Logs\LogsController;
use App\Http\Controllers\Users\ListUsersController;
use App\Http\Controllers\Analytics\AnalyticsUsersController;
use App\Http\Controllers\Cataloging\CertificationTypesController;
use App\Http\Controllers\Cataloging\CompetencesLearningsResultsController;
use App\Http\Controllers\Credentials\StudentsCredentialsController;
use App\Http\Controllers\Credentials\TeachersCredentialsController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MyProfileController;
use App\Http\Controllers\Notifications\EmailNotificationsController;
use App\Http\Controllers\Notifications\NotificationsChangesStatusesCoursesController;
use App\Http\Controllers\Notifications\NotificationsTypesController;
use App\Http\Controllers\RecoverPasswordController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\Webhooks\UpdateUserImageController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\Notifications\NotificationsPerUsersController;
use App\Http\Controllers\Webhooks\FileController;
use App\Http\Controllers\CertificateTestAccessController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::middleware(['combined.auth'])->group(function () {

    Route::get('/', function () {
        return view('home');
    });

    Route::middleware(['role:ADMINISTRATOR'])->group(function () {
        Route::get('/administration/general', [GeneralAdministrationController::class, 'index'])->name('administracion-general');
        Route::get('/administration/management_permissions', [ManagementPermissionsController::class, 'index'])->name('management-permissions');
        Route::get('/administration/footer_pages', [FooterPagesController::class, 'index'])->name('footer-pages');
        Route::get('/administration/header_pages', [HeaderPagesController::class, 'index'])->name('header-pages');

        Route::get('/administration/suggestions_improvements', [SuggestionsImprovementsController::class, 'index'])->name('suggestions-improvements');
        Route::get('/administration/redirection_queries_educational_program_types', [RedirectionQueriesEducationalProgramTypesController::class, 'index'])->name('redirection-queries-educational-program-types');
        Route::get('/administration/lanes_show', [LanesShowController::class, 'index'])->name('lanes-show');
        Route::get('/administration/payments', [PaymentsController::class, 'index'])->name('administration-payments');
        Route::get('/administration/login_systems', [LoginSystemsController::class, 'index'])->name('login-systems');
        Route::get('/administration/authentication_configuration', [AuthenticationConfigurationController::class, 'index'])->name('administration-authentication');
        Route::get('/administration/lms_systems', [LmsSystemsController::class, 'index'])->name('lms-systems');

        Route::get('/administration/centres', [CentersController::class, 'index'])->name('centres');

        Route::get('/administration/api_keys', [ApiKeysController::class, 'index'])->name('api-keys');

        Route::post('/administration/save_smtp_email_form', [GeneralAdministrationController::class, 'saveSMTPEmailForm']);
        Route::post('/administration/save_logo_image', [GeneralAdministrationController::class, 'saveLogoImage']);
        Route::post('/administration/restore_logo_image', [GeneralAdministrationController::class, 'restoreLogoImage']);
        Route::post('/administration/change_colors', [GeneralAdministrationController::class, 'changeColors']);
        Route::post('/administration/save_university_info', [GeneralAdministrationController::class, 'saveUniversityInfo']);
        Route::post('/administration/save_general_options', [GeneralAdministrationController::class, 'saveGeneralOptions']);
        Route::post('/administration/save_scripts', [GeneralAdministrationController::class, 'saveScripts']);
        Route::post('/administration/save_rrss', [GeneralAdministrationController::class, 'saveRrss']);
        Route::post('/administration/save_carrousel', [GeneralAdministrationController::class, 'saveCarrousel']);

        Route::post('/administration/add_font', [GeneralAdministrationController::class, 'addFont']);
        Route::delete('/administration/delete_font', [GeneralAdministrationController::class, 'deleteFont']);

        Route::get('/administration/api_keys/get_api_keys', [ApiKeysController::class, 'getApiKeys']);
        Route::get('/administration/api_keys/get_api_key/{api_key_uid}', [ApiKeysController::class, 'getApiKey']);
        Route::post('/administration/api_keys/save_api_key', [ApiKeysController::class, 'saveApiKey']);

        Route::post('/administration/login_systems/save_google_login', [LoginSystemsController::class, 'submitGoogleForm'])->name('google-login');
        Route::post('/administration/login_systems/save_facebook_login', [LoginSystemsController::class, 'submitFacebookForm'])->name('facebook-login');
        Route::post('/administration/login_systems/save_twitter_login', [LoginSystemsController::class, 'submitTwitterForm'])->name('twitter-login');
        Route::post('/administration/login_systems/save_linkedin_login', [LoginSystemsController::class, 'submitLinkedinForm'])->name('linkedin-login');

        Route::post('/administration/save_manager_permissions', [ManagementPermissionsController::class, 'saveManagersPermissionsForm']);
        Route::post('/administration/suggestions_improvements/save_email', [SuggestionsImprovementsController::class, 'saveEmail']);
        Route::get('/administration/suggestions_improvements/get_emails', [SuggestionsImprovementsController::class, 'getEmails']);
        Route::post('/administration/suggestions_improvements/delete_emails', [SuggestionsImprovementsController::class, 'deleteEmails']);

        Route::get('/administration/redirection_queries_educational_program_types/get_redirections_queries', [RedirectionQueriesEducationalProgramTypesController::class, 'getRedirectionsQueries']);
        Route::get('/administration/redirection_queries_educational_program_types/get_redirection_query/{redirection_query_uid}', [RedirectionQueriesEducationalProgramTypesController::class, 'getRedirectionQuery']);


        Route::post('/administration/redirection_queries_educational_program_types/save_redirection_query', [RedirectionQueriesEducationalProgramTypesController::class, 'saveRedirectionQuery']);
        Route::delete('/administration/redirection_queries_educational_program_types/delete_redirections_queries', [RedirectionQueriesEducationalProgramTypesController::class, 'deleteRedirectionsQueries']);

        Route::post('/administration/lanes_show/save_lanes_show', [LanesShowController::class, 'saveLanesShow']);
        Route::post('/administration/payments/save_payments_form', [PaymentsController::class, 'savePaymentsForm']);

        Route::post('/administration/lms_systems/save_lms_system', [LmsSystemsController::class, 'saveLmsSystem']);
        Route::get('/administration/lms_systems/get_lms_system/{lms_system_uid}', [LmsSystemsController::class, 'getLmsSystem']);
        Route::get('/administration/lms_systems/get_lms_systems', [LmsSystemsController::class, 'getLmsSystems']);
        Route::delete('/administration/lms_systems/delete_lms_systems', [LmsSystemsController::class, 'deleteLmsSystems']);

        Route::post('/administration/centers/save_center', [CentersController::class, 'saveCenter']);
        Route::get('/administration/centers/get_center/{centre_uid}', [CentersController::class, 'getCenter']);
        Route::get('/administration/centers/get_centers', [CentersController::class, 'getCenters']);
        Route::delete('/administration/centers/delete_centers', [CentersController::class, 'deleteCenters']);

        Route::get('/administration/carrousels', [CarrouselsController::class, 'index'])->name('carrousels');
        Route::post('/administration/carrousels/save_big_carrousels_approvals', [CarrouselsController::class, 'save_big_carrousels_approvals']);
        Route::post('/administration/carrousels/save_small_carrousels_approvals', [CarrouselsController::class, 'save_small_carrousels_approvals']);


        Route::get('/notifications/notifications_types', [NotificationsTypesController::class, 'index'])->name('notifications-types');
        Route::get('/notifications/notifications_types/get_list_notification_types', [NotificationsTypesController::class, 'getNotificationsTypes']);
        Route::get('/notifications/notifications_types/get_notification_type/{notification_type_uid}', [NotificationsTypesController::class, 'getNotificationType']);
        Route::post('/notifications/notifications_types/save_notification_type', [NotificationsTypesController::class, 'saveNotificationType']);
        Route::delete('/notifications/notifications_types/delete_notifications_types', [NotificationsTypesController::class, 'deleteNotificationsTypes']);

        Route::get('/notifications/notifications_per_users', [NotificationsPerUsersController::class, 'index'])->name('notifications-per-users');
        Route::get('/notifications/notifications_per_users/get_list_users', [NotificationsPerUsersController::class, 'getNotificationsPerUsers']);
        Route::get('/notifications/notifications_per_users/get_notifications/{user_uid}', [NotificationsPerUsersController::class, 'getNotificationsPerUser']);
    });

    Route::middleware(['role:ADMINISTRATOR,MANAGEMENT'])->group(function () {
        Route::get('/management/general_configuration', [ManagementGeneralConfigurationController::class, 'index'])->name('management-general-configuration');
        Route::get('/management/calls', [CallsController::class, 'index'])->name('management-calls');
        Route::get('/management/calls/get_calls', [CallsController::class, 'getCalls']);
        Route::post('/management/general_configuration/save_general_options', [ManagementGeneralConfigurationController::class, 'saveGeneralOptions']);
        Route::post('/management/general_configuration/save_teachers_automatic_aproval', [ManagementGeneralConfigurationController::class, 'saveTeachersAutomaticAproval']);
        Route::post('/management/calls/save_call', [CallsController::class, 'saveCall']);
        Route::get('/management/calls/get_call/{call_uid}', [CallsController::class, 'getCall']);
        Route::delete('/management/calls/delete_calls', [CallsController::class, 'deleteCalls']);

        Route::post('/administration/footer_pages/get_footer_pages', [FooterPagesController::class, 'getFooterPages']);
        Route::get('/administration/footer_pages/get_footer_page/{footer_page_uid}', [FooterPagesController::class, 'getFooterPage']);
        Route::post('/administration/footer_pages/save_footer_page', [FooterPagesController::class, 'saveFooterPage']);
        Route::delete('/administration/footer_pages/delete_footer_pages', [FooterPagesController::class, 'deleteFooterPages']);

        Route::post('/administration/header_pages/get_header_pages', [HeaderPagesController::class, 'getHeaderPages']);
        Route::get('/administration/header_pages/get_header_page/{footer_page_uid}', [HeaderPagesController::class, 'getHeaderPage']);
        Route::post('/administration/header_pages/save_header_page', [HeaderPagesController::class, 'saveHeaderPage']);
        Route::delete('/administration/header_pages/delete_header_pages', [HeaderPagesController::class, 'deleteHeaderPages']);

        Route::get('/cataloging/categories/get_categories', [CategoriesController::class, 'getCategories']);
        Route::post('/cataloging/categories/save_category', [CategoriesController::class, 'saveCategory']);
        Route::get('/cataloging/categories/get_list_categories', [CategoriesController::class, 'getListCategories']);
        Route::get('/cataloging/categories/get_category/{category_uid}', [CategoriesController::class, 'getCategory']);
        Route::get('/cataloging/categories/get_all_categories', [CategoriesController::class, 'getAllCategories']);
        Route::delete('/cataloging/categories/delete_categories', [CategoriesController::class, 'deleteCategories']);
        Route::get('/cataloging/competences_learnings_results/get_competences', [CompetencesLearningsResultsController::class, 'getCompetences']);
        Route::post('/cataloging/competences_learnings_results/save_competence', [CompetencesLearningsResultsController::class, 'saveCompetence']);
        Route::post('/cataloging/competences_learnings_results/save_learning_result', [CompetencesLearningsResultsController::class, 'saveLearningResult']);
        Route::get('/cataloging/competences_learnings_results/get_learning_result/{learning_result_uid}', [CompetencesLearningsResultsController::class, 'getLearningResult']);


        Route::get('/cataloging/competences_learnings_results/get_list_competences', [CompetencesLearningsResultsController::class, 'getListCompetences']);
        Route::get('/cataloging/competences_learnings_results/get_competence/{competence_uid}', [CompetencesLearningsResultsController::class, 'getCompetence']);
        Route::get('/cataloging/competences_learnings_results/get_all_competences', [CompetencesLearningsResultsController::class, 'getAllCompetences']);
        Route::delete('/cataloging/competences_learnings_results/delete_competences_learning_results', [CompetencesLearningsResultsController::class, 'deleteCompetencesLearningResults']);

        Route::post("/cataloging/competences_learnings_results/import_esco_framework", [CompetencesLearningsResultsController::class, 'importEscoFramework']);


        Route::get('/cataloging/course_types/get_list_course_types', [CourseTypesController::class, 'getCourseTypes']);
        Route::get('/cataloging/course_types/get_course_type/{course_type_uid}', [CourseTypesController::class, 'getCourseType']);
        Route::post('/cataloging/course_types/save_course_type', [CourseTypesController::class, 'saveCourseType']);
        Route::delete('/cataloging/course_types/delete_course_types', [CourseTypesController::class, 'deleteCourseTypes']);
        Route::get('/cataloging/certification_types', [CertificationTypesController::class, 'index'])->name('cataloging-certification-types');
        Route::get('/cataloging/certification_types/get_list_certification_types', [CertificationTypesController::class, 'getCertificationTypes']);
        Route::get('/cataloging/certification_types/get_certification_type/{certification_types_uid}', [CertificationTypesController::class, 'getCertificationType']);
        Route::post('/cataloging/certification_types/save_certification_type', [CertificationTypesController::class, 'saveCertificationType']);
        Route::delete('/cataloging/certification_types/delete_certification_types', [CertificationTypesController::class, 'deleteCertificationTypes']);
        Route::get('/cataloging/educational_resources_types/get_list_educational_resource_types', [EducationalResourceTypesController::class, 'getEducationalResourceTypes']);
        Route::get('/cataloging/educational_resources_types/get_educational_resource_type/{educational_resource_type_uid}', [EducationalResourceTypesController::class, 'getEducationalResourceType']);
        Route::post('/cataloging/educational_resources_types/save_educational_resource_type', [EducationalResourceTypesController::class, 'saveEducationalResourceType']);
        Route::delete('/cataloging/educational_resources_types/delete_educational_resource_types', [EducationalResourceTypesController::class, 'deleteEducationalResourceTypes']);
        Route::get('/cataloging/educational_program_types/get_list_educational_program_types', [EducationalProgramTypesController::class, 'getEducationalProgramTypes']);
        Route::get('/cataloging/educational_program_types/get_educational_program_type/{educational_program_type_uid}', [EducationalProgramTypesController::class, 'getEducationalProgramType']);
        Route::post('/cataloging/educational_program_types/save_educational_program_type', [EducationalProgramTypesController::class, 'saveEducationalProgramType']);
        Route::delete('/cataloging/educational_program_types/delete_educational_program_types', [EducationalProgramTypesController::class, 'deleteEducationalProgramTypes']);
        Route::get('/cataloging/categories', [CategoriesController::class, 'index'])->name('cataloging-categories');
        Route::get('/cataloging/competences_learnings_results', [CompetencesLearningsResultsController::class, 'index'])->name('cataloging-competences-learning-results');
        Route::get('/cataloging/course_types', [CourseTypesController::class, 'index'])->name('cataloging-course-types');
        Route::get('/cataloging/educational_resources_types', [EducationalResourceTypesController::class, 'index'])->name('cataloging-educational-resources');
        Route::get('/cataloging/educational_program_types', [EducationalProgramTypesController::class, 'index'])->name('cataloging-educational-program-types');




    });

    Route::middleware(['role:ADMINISTRATOR,MANAGEMENT,TEACHER'])->group(function () {

        Route::get('/notifications/general', [GeneralNotificationsController::class, 'index'])->name('notifications-general');
        Route::get('/notifications/email', [EmailNotificationsController::class, 'index'])->name('notifications-email');
        Route::get('/notifications/general/get_list_general_notifications', [GeneralNotificationsController::class, 'getGeneralNotifications']);
        Route::get('/notifications/general/get_general_notification/{notification_general_uid}', [GeneralNotificationsController::class, 'getGeneralNotification']);
        Route::get('/notifications/general/get_general_notification_user/{notification_general_uid}', [GeneralNotificationsController::class, 'getGeneralNotificationUser']);

        Route::get('/notifications/notifications_statuses_courses/get_notifications_statuses_courses/{status_notification_uid}', [NotificationsChangesStatusesCoursesController::class, 'getNotificationsChangesStatusesCoursesController']);

        Route::post('/notifications/general/save_general_notifications', [GeneralNotificationsController::class, 'saveGeneralNotification']);
        Route::delete('/notifications/general/delete_general_notifications', [GeneralNotificationsController::class, 'deleteGeneralNotifications']);
        Route::get('/notifications/general/get_users_views_general_notification/{notification_general_uid}', [GeneralNotificationsController::class, 'getUserViewsGeneralNotification']);
        Route::get('notifications/email/get_list_email_notifications', [EmailNotificationsController::class, 'getEmailNotifications'])->name('get-notifications-email');
        Route::post('notifications/email/save_email_notification', [EmailNotificationsController::class, 'saveEmailNotification'])->name('save-notification-email');
        Route::get('notifications/email/get_email_notification/{notification_email_uid}', [EmailNotificationsController::class, 'getEmailNotification'])->name('get-notification-email');
        Route::delete('/notifications/email/delete_email_notifications', [EmailNotificationsController::class, 'deleteEmailNotifications']);
    });


    Route::middleware(['role:ADMINISTRATOR,MANAGEMENT,TEACHER'])->group(function () {
        Route::post('/learning_objects/courses/change_statuses_courses', [ManagementCoursesController::class, 'changeStatusesCourses']);
        Route::post('/learning_objects/courses/save_course', [ManagementCoursesController::class, 'saveCourse']);
        Route::post('/learning_objects/courses/filter_courses', [ManagementCoursesController::class, 'filterCourses']);
        Route::get('/learning_objects/courses/get_course/{course_uid}', [ManagementCoursesController::class, 'getCourse']);
        Route::get('/learning_objects/courses/get_course_students/{course_uid}', [ManagementCoursesController::class, 'getCourseStudents']);
        Route::post('/learning_objects/courses/save_course_students/{course_uid}', [ManagementCoursesController::class, 'saveCourseStudents']);
        Route::delete('/learning_objects/courses/delete_course_students', [ManagementCoursesController::class, 'deleteCourseStudents']);
        Route::post('/learning_objects/courses/approve_inscriptions_course', [ManagementCoursesController::class, 'approveInscriptionsCourse']);
        Route::post('/learning_objects/courses/reject_inscriptions_course', [ManagementCoursesController::class, 'rejectInscriptionsCourse']);
        Route::post('/learning_objects/courses/duplicate_course/{course_uid}', [ManagementCoursesController::class, 'duplicateCourse']);
        Route::post('/learning_objects/courses/new_edition_course/{course_uid}', [ManagementCoursesController::class, 'newEditionCourse']);
        Route::get('/learning_objects/educational_resources', [EducationalResourcesController::class, 'index'])->name('learning-objects-educational-resources');

        Route::get('/learning_objects/educational_resources_per_users', [EducationalResourcesPerUsersController::class, 'index'])->name('learning-objects-educational-resources-per-users');
        Route::get('/learning_objects/educational_resources_per_users/get_list_users', [EducationalResourcesPerUsersController::class, 'getEducationalResourcesPerUsers']);
        Route::get('/learning_objects/educational_resources_per_users/get_notifications/{user_uid}', [EducationalResourcesPerUsersController::class, 'getEducationalResourcesPerUser']);



        Route::get('/learning_objects/educational_programs', [EducationalProgramsController::class, 'index'])->name('learning-objects-educational-programs');
        Route::get('/learning_objects/courses', [ManagementCoursesController::class, 'index'])->name('courses');
        Route::post('/learning_objects/courses/get_courses', [ManagementCoursesController::class, 'getCourses']);
        Route::get('/learning_objects/courses/get_all_competences', [ManagementCoursesController::class, 'getAllCompetences']);
        Route::get('/learning_objects/educational_programs/get_educational_program_type', [ManagementCoursesController::class, 'getAllCompetences']);
        Route::post('/learning_objects/courses/enroll_students', [ManagementCoursesController::class, 'enrollStudents']);
        Route::post('/learning_objects/courses/enroll_students_csv', [ManagementCoursesController::class, 'enrollStudentsCsv']);



        Route::get('/learning_objects/educational_resources/get_resource/{uid}', [EducationalResourcesController::class, 'getResource']);
        Route::delete('/learning_objects/educational_resources/delete_resources', [EducationalResourcesController::class, 'deleteResources']);
        Route::post('/learning_objects/educational_resources/save_resource', [EducationalResourcesController::class, 'saveResource']);
        Route::get('/learning_objects/educational_resources/get_resources', [EducationalResourcesController::class, 'getResources']);
        Route::post('/learning_objects/educational_resources/change_statuses_resources', [EducationalResourcesController::class, 'changeStatusesResources']);
        Route::get('/learning_objects/educational_programs/get_educational_programs', [EducationalProgramsController::class, 'getEducationalPrograms']);
        Route::post('/learning_objects/educational_programs/save_educational_program', [EducationalProgramsController::class, 'saveEducationalProgram']);
        Route::get('/learning_objects/educational_programs/get_educational_program/{educational_program_uid}', [EducationalProgramsController::class, 'getEducationalProgram']);
        Route::delete('/learning_objects/educational_programs/delete_educational_programs', [EducationalProgramsController::class, 'deleteEducationalPrograms']);
        Route::get('/learning_objects/educational_programs/search_courses_without_educational_program/{search}', [EducationalProgramsController::class, 'searchCoursesWithoutEducationalProgram']);


        Route::get('/credentials/students', [StudentsCredentialsController::class, 'index'])->name('credentials-students');
        Route::get('/credentials/students/get_students', [StudentsCredentialsController::class, 'getStudents'])->name('credentials-get-students');
        Route::get('/credentials/students/get_courses_student/{student_uid}', [StudentsCredentialsController::class, 'getCoursesStudents'])->name('credentials-get-courses-student');
        Route::get('/credentials/teachers', [TeachersCredentialsController::class, 'index'])->name('credentials-teachers');
        Route::get('/credentials/teachers/get_teachers', [TeachersCredentialsController::class, 'getTeachers'])->name('credentials-get-teachers');
        Route::get('/credentials/teachers/get_courses_teacher/{teacher_uid}', [TeachersCredentialsController::class, 'getCoursesTeacher'])->name('credentials-get-courses-teacher');
    });

    Route::middleware(['role:ADMINISTRATOR,MANAGEMENT'])->group(function () {
        Route::get('/logs/list_logs', [LogsController::class, 'index'])->name('list-logs');
        Route::get('/analytics/users', [AnalyticsUsersController::class, 'index'])->name('analytics-users');

        Route::get('/logs/list_logs/get_logs', [LogsController::class, 'getLogs']);

        Route::get('/analytics/users/get_user_roles', [AnalyticsUsersController::class, 'getUsersRoles'])->name('analytics-users-roles');
    });


    Route::get('/users/list_users', [ListUsersController::class, 'index'])->name('list-users');
    Route::get('/users/list_users/get_users', [ListUsersController::class, 'getUsers']);
    Route::get('/users/list_users/search_users/{search}', [ListUsersController::class, 'searchUsers']);
    Route::get('/users/list_users/search_users_backend/{search}', [ListUsersController::class, 'searchUsersBackend']);
    Route::get('/users/list_users/search_users_no_enrolled/{course}/{search}', [ListUsersController::class, 'searchUsersNoEnrolled']);

    Route::get('/users/list_users/get_user_roles', [ListUsersController::class, 'getUserRoles']);
    Route::get('/users/list_users/get_user/{user_uid}', [ListUsersController::class, 'getUser']);
    Route::post('/users/list_users/save_user', [ListUsersController::class, 'saveUser']);
    Route::delete('/users/list_users/delete_users', [ListUsersController::class, 'deleteUsers']);


    Route::get('/refresh-csrf', function () {
        return csrf_token();
    });


    Route::get('/download_file/{filename}', function ($filename) {
        $path = 'files/' . $filename;
        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $file = Storage::disk('local')->get($path);
        $type = Storage::disk('local')->mimeType($path);

        $download = Response::make($file, 200);
        $download->header("Content-Type", $type);

        return $download;
    });

    Route::get('/my_profile', [MyProfileController::class, 'index'])->name('my-profile');
    Route::post('/my_profile/update', [MyProfileController::class, 'updateUser']);
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'index'])->name('login');

    Route::get('/forgot_password', [LoginController::class, 'index']);

    Route::post('/login/authenticate', [LoginController::class, 'authenticate']);

    Route::get('auth/google', [LoginController::class, 'redirectToGoogle']);
    Route::get('auth/google/callback', [LoginController::class, 'handleGoogleCallback']);

    Route::get('auth/twitter', [LoginController::class, 'redirectToTwitter']);
    Route::get('auth/twitter/callback', [LoginController::class, 'handleTwitterCallback']);

    Route::get('auth/linkedin',  [LoginController::class, 'redirectToLinkedin']);
    Route::get('auth/linkedin/callback',  [LoginController::class, 'handleLinkedinCallback']);

    Route::get('auth/facebook', [LoginController::class, 'redirectToFacebook']);
    Route::get('auth/facebook/callback', [LoginController::class, 'handleFacebookCallback']);
});
Route::get('/logout', [LoginController::class, 'logout']);

Route::get('/recover_password', [RecoverPasswordController::class, 'index'])->name('recover-password');
Route::post('/recover_password/send', [RecoverPasswordController::class, 'recoverPassword']);

Route::get('/reset_password/{token}', [ResetPasswordController::class, 'index'])->name('reset-password');
Route::post('/reset_password/send', [ResetPasswordController::class, 'resetPassword']);


Route::middleware(['api_auth'])->group(function () {
    Route::post('/webhook/update_user_image', [UpdateUserImageController::class, 'index']);
    Route::post('/api/upload_file', [FileController::class, 'uploadFile']);
    Route::post('/api/download_file', [FileController::class, 'downloadFile']);
});

Route::get('/certificate-access', [CertificateTestAccessController::class, 'index'])->name('certificate-access');
