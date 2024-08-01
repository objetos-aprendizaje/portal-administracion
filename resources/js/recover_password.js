import { showToast } from "./toast";

document.addEventListener("DOMContentLoaded", function () {
    blockButtonOnSubmit("recoverPasswordFormDesktop");
    blockButtonOnSubmit("recoverPasswordFormMobile");
});

function blockButtonOnSubmit(formId) {
    const form = document.getElementById(formId);
    const submitButton = form.querySelector('button[type="submit"]');

    form.addEventListener("submit", function (event) {
        const email = form.querySelector('input[name="email"]').value;

        if(email === "") {
            showToast("Debes especificar un email", "error");
            event.preventDefault();
            return;
        }

        // bloqueamos el botón del submit
        submitButton.disabled = true;
    });
}
