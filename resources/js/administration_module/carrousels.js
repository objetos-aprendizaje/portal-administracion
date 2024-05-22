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
            courses: values
        },
        toast: true,
        loader: true,
        stringify: true
    };

    apiFetch(params);
}

function submitSmallCoursesCarrouselsForm() {

    const values = getInputsChecksForm(this);

    const params = {
        url: "/administration/carrousels/save_small_carrousels_approvals",
        method: "POST",
        body: {
            courses: values
        },
        toast: true,
        loader: true,
        stringify: true
    };

    apiFetch(params);
}

function getInputsChecksForm(form) {
    var checkedInputs = Array.from(
        form.querySelectorAll("input[type=checkbox]:checked")
    );

    var values = checkedInputs.map((input) => input.name);

    return values;
}
