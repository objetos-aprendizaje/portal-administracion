import { showToast } from "./toast.js";

document.addEventListener("DOMContentLoaded", function () {

    recoverPassword("recoverPasswordFormDesktop");
    recoverPassword("recoverPasswordFormMobile");

    if(error) showToast(error, "error");

});

function recoverPassword(formId) {
    const form = document.getElementById(formId);
    const submitButton = form.querySelector('button[type="submit"]');

    form.addEventListener("submit", function (event) {


        event.preventDefault();

        const email = event.target.elements.email;

        if(!email.value) {
            showToast("El campo de correo electrónico es obligatorio", "error");
            return;
        }

        // Deshabilitar el botón de envío
        submitButton.disabled = true;

        fetch("/recover_password/send", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: JSON.stringify({
                email: email.value,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.reset) {
                    showToast(data.message, "success");
                    email.value = "";
                } else {
                    showToast(data.message, "error");
                    console.error(data.message);
                }
            })
            .catch((error) => {
                console.error("Error:", error);
            })
            .finally(() => {
                // Habilitar el botón de envío
                submitButton.disabled = false;
            });
    });
}
