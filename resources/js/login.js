import { showToast } from "./toast.js";

document.addEventListener("DOMContentLoaded", function () {
    setImageBackgroundHeight();

    window.addEventListener("resize", setImageBackgroundHeight);

    login("loginFormDesktop");
    login("loginFormMobile");

    checkErrorMessages();
});

function login(formId) {
    document
        .getElementById(formId)
        .addEventListener("submit", function (event) {
            event.preventDefault();

            const email = event.target.elements.email.value;
            const password = event.target.elements.password.value;

            fetch("/login/authenticate", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
                body: JSON.stringify({
                    email: email,
                    password: password,
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.authenticated) {
                        window.location.href = "/";
                    } else {
                        showToast(data.error, "error");
                        console.error(data.error);
                    }
                })
                .catch((error) => {
                    console.error("Error:", error);
                });
        });
}

function setImageBackgroundHeight() {
    const body = document.body;

    const img = document.getElementById("image-background");

    // Verifica si hay scroll vertical
    if (body.scrollHeight > window.innerHeight) {
        console.log("scroll");
        img.classList.remove("h-screen");
        img.classList.add("h-full"); // Aplica h-full si no hay scroll
    } else {
        img.classList.remove("h-full");
        img.classList.add("h-screen"); // Aplica h-screen si hay scroll
    }
}

function checkErrorMessages() {
    if (window.errors) {
        window.errors.forEach((error) => {
            showToast(error, "error");
        });
    }

        // Función para obtener los parámetros de la URL
        function getParameterByName(name) {
            const url = new URL(window.location.href);
            const paramValue = url.searchParams.get(name);
            return paramValue ? decodeURIComponent(paramValue) : null;
        }

        // Obtener los errores de la URL
        const errorsParam = getParameterByName('errors');

        if (errorsParam) {
            try {
                // Parsear el JSON de errores
                window.errors = JSON.parse(errorsParam);

                // Mostrar los errores en la consola o en el DOM
                window.errors.forEach(function(error) {
                    console.error(error); // O mostrarlo en el DOM
                });

                // Opcional: Limpiar el parámetro de la URL sin recargar la página
                const url = new URL(window.location);
                url.searchParams.delete('errors');
                window.history.replaceState({}, document.title, url.toString());
            } catch (e) {
                console.error('Error al parsear los errores:', e);
            }
        }

        function getParameterByName(name) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(name);
        }
        const paramE = getParameterByName('e');
        if (paramE == "certificate-error"){
            showToast("Error de acceso mediante certificado digital");
        }

}
