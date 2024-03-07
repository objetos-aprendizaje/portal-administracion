import { apiFetch } from "../app.js";

document.addEventListener("DOMContentLoaded", function () {
    document
        .getElementById("google-login-form")
        .addEventListener("submit", submitGoogleForm);
    document
        .getElementById("facebook-login-form")
        .addEventListener("submit", submitFacebookForm);
    document
        .getElementById("twitter-login-form")
        .addEventListener("submit", submitTwitterForm);
    document
        .getElementById("linkedin-login-form")
        .addEventListener("submit", submitLinkedinForm);
});

function submitGoogleForm() {
    const formData = new FormData(this);
    submitForm(formData, "/administration/login_systems/save_google_login");
}

function submitFacebookForm() {
    const formData = new FormData(this);
    submitForm(formData, "/administration/login_systems/save_facebook_login");
}

function submitTwitterForm() {
    const formData = new FormData(this);
    submitForm(formData, "/administration/login_systems/save_twitter_login");
}

function submitLinkedinForm() {
    const formData = new FormData(this);
    submitForm(formData, "/administration/login_systems/save_linkedin_login");
}

function submitForm(formData, url) {

    formData.append("google_login_active", document.getElementById("google_login_active").checked ? 1 : 0);
    formData.append("facebook_login_active", document.getElementById("facebook_login_active").checked ? 1 : 0);
    formData.append("twitter_login_active", document.getElementById("twitter_login_active").checked ? 1 : 0);
    formData.append("linkedin_login_active", document.getElementById("linkedin_login_active").checked ? 1 : 0);

    const params = {
        url: url,
        method: "POST",
        body: formData,
        toast: true,
        loader: true,
    };

    apiFetch(params);
}
