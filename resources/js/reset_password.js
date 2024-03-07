import { showToast } from "./toast.js";


document.addEventListener("DOMContentLoaded", function () {

    resetPassword("resetPasswordDesktop");
    resetPassword("resetPasswordMobile");
});


function resetPassword(formId) {
    const form = document.getElementById(formId);
    const submitButton = form.querySelector('button[type="submit"]');


    form.addEventListener("submit", function (event) {


        event.preventDefault();

        const password = event.target.elements.password.value;
        const confirm_password = event.target.elements.confirm_password.value;


        if(password != confirm_password) {
            showToast("Las contraseñas no coinciden", "error");
            return;
        }

        // Deshabilitar el botón de envío
        submitButton.disabled = true;

        fetch("/reset_password/send", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: JSON.stringify({
                password: password,
                token: event.target.elements.token.value,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.reset) {
                    showToast(data.message, "success");

                    setTimeout(() => {
                        window.location.href = "/login";
                    }, 2000);
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
