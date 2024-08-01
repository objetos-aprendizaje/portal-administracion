import {
    showFormErrors,
    resetFormErrors,
    apiFetch
} from "../app.js";


document.addEventListener("DOMContentLoaded", function () {
    initHandlers();
});

function initHandlers() {
    document
        .getElementById("certidigital-form")
        .addEventListener("submit", submitFormCertidigital);
}

function submitFormCertidigital() {
    const formData = new FormData(this);

    const params = {
        url: "/administration/certidigital/save_certidigital_form",
        method: "POST",
        body: formData,
        toast: true,
        loader: true,
    };

    resetFormErrors("certidigital-form");

    apiFetch(params).catch((data) => {
        showFormErrors(data.errors);
    });

    resetFormErrors();
}
