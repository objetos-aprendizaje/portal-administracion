import {
    getMultipleTomSelectInstance,
    apiFetch,
} from "../app.js";

let selectTeachers;

document.addEventListener("DOMContentLoaded", function () {
    initHandlers();

    selectTeachers = getMultipleTomSelectInstance("#select-teacher");
});

function initHandlers() {

    document
        .getElementById("save-teachers-automatic-approval")
        .addEventListener("click", saveTeachersAutomaticApproval);

    document
        .getElementById("management-general-configuration-form")
        .addEventListener("submit", saveGeneralConfigurationOptions);
}

function saveTeachersAutomaticApproval() {

    const selectedTeachers = selectTeachers.items;

    const params = {
        url: "/management/general_configuration/save_teachers_automatic_aproval",
        method: "POST",
        body: {selectedTeachers: selectedTeachers},
        toast: true,
        loader: true,
        stringify: true
    };

    apiFetch(params);
}

/**
 * Checkboxs de configuración general
 **/
function saveGeneralConfigurationOptions() {

    const params = {
        url: "/management/general_configuration/save_general_options",
        method: "POST",
        body: {
            "necessary_approval_courses": document.getElementById("necessary_approval_courses").checked,
            "necessary_approval_resources": document.getElementById("necessary_approval_resources").checked,
            "necessary_approval_editions": document.getElementById("necessary_approval_editions").checked,
            "course_status_change_notifications": document.getElementById("course_status_change_notifications").checked,
        },
        toast: true,
        loader: true,
        stringify: true
    };

    apiFetch(params);
}
