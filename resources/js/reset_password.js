import { showToast } from "./toast.js";

document.addEventListener("DOMContentLoaded", function () {

    if (window.errors) {
        window.errors.forEach((errorMessage) => {
            showToast(errorMessage, "error");
        });
    }
});
