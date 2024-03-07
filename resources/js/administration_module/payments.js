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
        .getElementById("payments-form")
        .addEventListener("submit", submitFormPayments);
}

function submitFormPayments() {
    const formData = new FormData(this);

    const params = {
        url: "/administration/payments/save_payments_form",
        method: "POST",
        body: formData,
        toast: true,
        loader: true,
    };

    resetFormErrors();

    apiFetch(params).catch((data) => {
        showFormErrors(data.errors);
    });

    resetFormErrors();
}
