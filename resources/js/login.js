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
}
