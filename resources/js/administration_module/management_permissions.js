import { apiFetch } from "../app.js";

document.addEventListener("DOMContentLoaded", function () {
    initHandlers();
});

function initHandlers() {
    document
        .getElementById("managers-permissions-form")
        .addEventListener("submit", submitFormManagersPermissions);
}

/**
 * Envío del formulario de checks de permisos a gestores
 **/
function submitFormManagersPermissions() {
    const params = {
        url: "/administration/save_manager_permissions",
        method: "POST",
        body: {
            managers_can_manage_categories: document.getElementById("managers_can_manage_categories").checked,
            managers_can_manage_course_types: document.getElementById("managers_can_manage_course_types").checked,
            managers_can_manage_educational_resources_types: document.getElementById("managers_can_manage_educational_resources_types").checked,
            managers_can_manage_calls: document.getElementById("managers_can_manage_calls").checked,
        },
        toast: true,
        loader: true,
        stringify: true
    };

    apiFetch(params);
}
