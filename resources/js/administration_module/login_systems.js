import { apiFetch, showFormErrors, resetFormErrors } from "../app.js";

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
    document
        .getElementById("cas-login-form")
        .addEventListener("submit", submitCasForm);
    document
        .getElementById("rediris-login-form")
        .addEventListener("submit", submitRedirisForm);
});

function submitGoogleForm() {
    const formData = new FormData(this);
    submitForm(formData, "/administration/login_systems/save_google_login", this.id);
}

function submitFacebookForm() {
    const formData = new FormData(this);
    submitForm(formData, "/administration/login_systems/save_facebook_login", this.id);
}

function submitTwitterForm() {
    const formData = new FormData(this);
    submitForm(formData, "/administration/login_systems/save_twitter_login", this.id);
}

function submitLinkedinForm() {
    const formData = new FormData(this);
    submitForm(formData, "/administration/login_systems/save_linkedin_login", this.id);
}

function submitCasForm() {
    const formData = new FormData(this);
    submitForm(formData, "/administration/login_systems/save_cas_login", this.id);
}

function submitRedirisForm() {
    const formData = new FormData(this);
    submitForm(formData, "/administration/login_systems/save_rediris_login", this.id);
}

function submitForm(formData, url, formId) {
    formData.append(
        "google_login_active",
        document.getElementById("google_login_active").checked ? 1 : 0
    );
    formData.append(
        "facebook_login_active",
        document.getElementById("facebook_login_active").checked ? 1 : 0
    );
    formData.append(
        "twitter_login_active",
        document.getElementById("twitter_login_active").checked ? 1 : 0
    );
    formData.append(
        "linkedin_login_active",
        document.getElementById("linkedin_login_active").checked ? 1 : 0
    );
    formData.append(
        "cas_login_active",
        document.getElementById("cas_login_active").checked ? 1 : 0
    );
    formData.append(
        "rediris_login_active",
        document.getElementById("rediris_login_active").checked ? 1 : 0
    );

    const params = {
        url: url,
        method: "POST",
        body: formData,
        toast: true,
        loader: true,
    };

    resetFormErrors(formId);

    apiFetch(params).catch((data) => {
        showFormErrors(data.errors);
    });
}
