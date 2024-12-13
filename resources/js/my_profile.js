import {
    updateInputImage,
    resetFormErrors,
    showFormErrors,
    apiFetch,
} from "./app";

document.addEventListener("DOMContentLoaded", function () {
    updateInputImage(6144);

    initHandlers();

    controlChangesGeneralNotificationsCheckboxes();
    controlChangesEmailNotificationsCheckboxes();
});

function initHandlers() {
    document
        .getElementById("user-profile-form")
        .addEventListener("submit", submitUserProfileForm);

    document
        .getElementById("delete-photo")
        .addEventListener("click", deletePhoto);
}

function deletePhoto() {
    const params = {
        method: "DELETE",
        toast: true,
        loader: true,
        url: "/my_profile/delete_photo",
    };

    apiFetch(params).then(() => {
        document.getElementById("photo_path_preview").src =
            "/data/images/default_images/no_image_attached.svg";
        document.getElementById("image-name").textContent =
            "Ningún archivo seleccionado";
        document.getElementById("photo_path").value = "";
    });
}

function submitUserProfileForm() {
    const formData = new FormData(this);

    formData.append(
        "general_notifications_allowed",
        document.getElementById("general_notifications_allowed").checked ? 1 : 0
    );
    formData.append(
        "email_notifications_allowed",
        document.getElementById("email_notifications_allowed").checked ? 1 : 0
    );

    // Recoger los uid de los checkboxes marcados con clase notification-type
    const generalNotificationTypes = document.querySelectorAll(
        ".general-notification-type:not(:checked)"
    );
    const generalNotificationTypesCheckedValues = Array.from(
        generalNotificationTypes
    ).map((checkbox) => checkbox.value);

    formData.append(
        "general_notification_types_disabled",
        JSON.stringify(generalNotificationTypesCheckedValues)
    );

    const emailNotificationTypes = document.querySelectorAll(
        ".email-notification-type:not(:checked)"
    );
    const emailNotificationTypesCheckedValues = Array.from(
        emailNotificationTypes
    ).map((checkbox) => checkbox.value);
    formData.append(
        "email_notification_types_disabled",
        JSON.stringify(emailNotificationTypesCheckedValues)
    );

    const automaticEmailNotificationTypes = document.querySelectorAll(
        ".automatic-email-notification-type:not(:checked)"
    );
    const automaticEmailNotificationTypesCheckedValues = Array.from(
        automaticEmailNotificationTypes
    ).map((checkbox) => checkbox.value);
    formData.append(
        "automatic_email_notification_types_disabled",
        JSON.stringify(automaticEmailNotificationTypesCheckedValues)
    );

    // Recoger los uid de los checkboxes marcados con clase notification-type
    const automaticGeneralNotificationTypes = document.querySelectorAll(
        ".automatic-general-notification-type:not(:checked)"
    );
    const automaticGeneralNotificationTypesCheckedValues = Array.from(
        automaticGeneralNotificationTypes
    ).map((checkbox) => checkbox.value);
    formData.append(
        "automatic_general_notification_types_disabled",
        JSON.stringify(automaticGeneralNotificationTypesCheckedValues)
    );

    const params = {
        method: "POST",
        body: formData,
        toast: true,
        loader: true,
        url: "/my_profile/update",
    };

    resetFormErrors("user-profile-form");

    apiFetch(params)
        .then(() => {})
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

/**
 * Controla los cambios en los checkboxes de notificaciones generales.
 */
function controlChangesGeneralNotificationsCheckboxes() {
    // Encuentra los otros checkboxes
    let otherGeneralNotificationsCheckboxes = document.querySelectorAll(
        ".general-notification-type"
    );

    // Encuentra el checkbox "Notificaciones Generales"
    let generalNotificationsCheckbox = document.querySelector(
        "#general_notifications_allowed"
    );

    // Escucha los eventos de cambio en el checkbox "Notificaciones Generales"
    generalNotificationsCheckbox.addEventListener("change", function () {
        // Marca o desmarca los otros checkboxes para que coincidan con el estado del checkbox "Notificaciones Generales"
        otherGeneralNotificationsCheckboxes.forEach(function (checkbox) {
            checkbox.checked = generalNotificationsCheckbox.checked;
        });
    });

    // Añade un escuchador de eventos a cada checkbox de tipo
    otherGeneralNotificationsCheckboxes.forEach(function (checkbox) {
        checkbox.addEventListener("change", function () {
            // Si el checkbox de tipo está marcado, marca también el checkbox "Notificaciones Generales"
            if (checkbox.checked) {
                generalNotificationsCheckbox.checked = true;
            }
        });
    });
}

function controlChangesEmailNotificationsCheckboxes() {
    let otherEmailNotificationsCheckboxes = document.querySelectorAll(
        ".email-notification-type"
    );

    let emailNotificationsCheckbox = document.querySelector(
        "#email_notifications_allowed"
    );

    emailNotificationsCheckbox.addEventListener("change", function () {
        // Marca o desmarca los otros checkboxes para que coincidan con el estado del checkbox "Notificaciones por email"
        otherEmailNotificationsCheckboxes.forEach(function (checkbox) {
            checkbox.checked = emailNotificationsCheckbox.checked;
        });
    });

    otherEmailNotificationsCheckboxes.forEach(function (checkbox) {
        checkbox.addEventListener("change", function () {
            // Si el checkbox de tipo está marcado, marca también el checkbox "Notificaciones por email"
            if (checkbox.checked) {
                emailNotificationsCheckbox.checked = true;
            }
        });
    });
}
