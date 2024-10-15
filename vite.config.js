import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/css/toastify.css",
                "resources/scss/app.scss",
                "resources/js/heroicons.js",
                "resources/js/cookie_handler.js",
                "resources/js/coloris.min.js",
                "resources/css/coloris.min.css",
                "resources/js/app.js",
                "resources/js/menu.js",
                "resources/js/tabulator_handler.js",
                "resources/js/administration_module/general.js",
                "resources/js/administration_module/management_permissions.js",
                "resources/js/administration_module/footer_pages.js",
                "resources/js/administration_module/suggestions_improvements.js",
                "resources/js/administration_module/redirection_queries_educational_program_types.js",
                "node_modules/tinymce/tinymce.min.js",
                "resources/js/modal_handler.js",
                "resources/js/loading_handler.js",
                "resources/js/administration_module/lanes_show.js",
                "resources/js/learning_objects_module/courses.js",
                "node_modules/tabulator-tables/dist/js/tabulator.min.js",
                "node_modules/tabulator-tables/dist/css/tabulator.min.css",
                "resources/js/management_module/general_configuration.js",
                "node_modules/tom-select/dist/js/tom-select.complete.min.js",
                "node_modules/tom-select/dist/css/tom-select.min.css",
                "resources/js/management_module/calls.js",
                "resources/js/cataloging_module/categories.js",
                "resources/js/users_module/list_users.js",
                "resources/js/learning_objects_module/educational_resources.js",
                "resources/js/learning_objects_module/educational_programs.js",
                "resources/js/logs_module/list_logs.js",
                "resources/js/notifications_module/general_notifications.js",
                "resources/js/cataloging_module/course_types.js",
                "resources/js/cataloging_module/educational_resource_types.js",
                "resources/js/cataloging_module/educational_program_types.js",
                "resources/js/analytics_module/analytics_users.js",
                "resources/js/notifications_module/email_notifications.js",
                "resources/js/administration_module/payments.js",
                "node_modules/flatpickr/dist/flatpickr.css",
                "resources/js/cataloging_module/competences_learnings_results.js",
                "resources/js/notifications_handler.js",
                "resources/js/administration_module/login_systems.js",
                "resources/js/cataloging_module/certification_types.js",
                "resources/js/login.js",
                "resources/js/refresh_csrf_token.js",
                "node_modules/choices.js/public/assets/styles/choices.min.css",
                "node_modules/choices.js/public/assets/scripts/choices.min.js",
                "resources/js/credentials_module/students_credentials.js",
                "resources/js/credentials_module/teachers_credentials.js",
                "resources/js/administration_module/lms_systems.js",
                "resources/js/toast.js",
                "resources/js/recover_password.js",
                "resources/js/reset_password.js",
                "resources/js/administration_module/api_keys.js",
                "resources/js/administration_module/header_pages.js",
                "resources/js/my_profile.js",
                "resources/js/notifications_module/notifications_types.js",
                "node_modules/treeselectjs/dist/treeselectjs.css",
                "resources/js/administration_module/centers.js",
                "resources/js/administration_module/carrousels.js",
                "resources/js/notifications_module/notifications_per_users.js",
                "resources/js/learning_objects_module/educational_resources_per_users.js",
                'node_modules/infinite-tree/dist/infinite-tree.css',
                "resources/js/administration_module/certidigital_configuration.js",
                "resources/js/administration_module/licenses.js",
                "resources/js/administration_module/tooltip_texts.js",
                "resources/js/administration_module/departments.js",
                "resources/js/analytics_module/analytics_abandoned.js",
                "resources/js/analytics_module/analytics_poa.js",
                "resources/js/analytics_module/analytics_top_categories.js"
            ],
            refresh: true,
        }),
    ],
});
