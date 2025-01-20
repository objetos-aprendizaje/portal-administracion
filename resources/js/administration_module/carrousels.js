import { apiFetch } from "../app.js";

document.addEventListener("DOMContentLoaded", function () {
    initHandlers();
});

function initHandlers() {
    document
        .getElementById("big-courses-carrousels-form")
        .addEventListener("submit", submitBigCoursesCarrouselsForm);

    document
        .getElementById("small-courses-carrousels-form")
        .addEventListener("submit", submitSmallCoursesCarrouselsForm);
}

function submitBigCoursesCarrouselsForm() {
    const values = getInputsChecksForm(this);

    const params = {
        url: "/administration/carrousels/save_big_carrousels_approvals",
        method: "POST",
        body: {
            courses: values.courses,
            educationalPrograms: values.educational_programs,
        },
        toast: true,
        loader: true,
        stringify: true,
    };

    apiFetch(params);
}

function submitSmallCoursesCarrouselsForm() {
    const values = getInputsChecksForm(this);

    const params = {
        url: "/administration/carrousels/save_small_carrousels_approvals",
        method: "POST",
        body: {
            courses: values.courses,
            educationalPrograms: values.educational_programs,
        },
        toast: true,
        loader: true,
        stringify: true,
    };

    apiFetch(params);
}

function getInputsChecksForm(form) {
    const allCoursesInputs = Array.from(
        form.querySelectorAll("input[type=checkbox][data-type='course']")
    );

    const allEducationalProgramsInputs = Array.from(
        form.querySelectorAll(
            "input[type=checkbox][data-type='educational_program']"
        )
    );

    const courses = allCoursesInputs.map((input) => ({
        uid: input.name,
        checked: input.checked,
    }));

    const educationalPrograms = allEducationalProgramsInputs.map((input) => ({
        uid: input.name,
        checked: input.checked,
    }));

    return {
        courses: courses,
        educational_programs: educationalPrograms,
    };
}
