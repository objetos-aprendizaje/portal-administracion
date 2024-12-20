import {
    showFormErrors,
    resetFormErrors,
    apiFetch
} from "../app.js";


document.addEventListener("DOMContentLoaded", function () {
    initHandlers();
    isCheckboxChecked();
});

function initHandlers() {
    document
        .getElementById("payments-form")
        .addEventListener("submit", submitFormPayments);

    document
        .getElementById("payment_gateway")
        .addEventListener('change', isCheckboxChecked);

}

function submitFormPayments() {
    const formData = new FormData(this);

    formData.append("payment_gateway", document.getElementById("payment_gateway").checked ? "1" : "0");

    const params = {
        url: "/administration/payments/save_payments_form",
        method: "POST",
        body: formData,
        toast: true,
        loader: true,
    };

    resetFormErrors("payments-form");

    apiFetch(params).catch((data) => {
        showFormErrors(data.errors);
    });

    resetFormErrors();
}

function isCheckboxChecked(){
    const checkbox = document.getElementById('payment_gateway');
    const div = document.getElementById('payment_redsys');

    if (checkbox.checked){
        div.classList.remove('hidden');
    }else{
        div.classList.add('hidden');
    }
}

