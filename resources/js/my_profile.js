import {
    updateInputImage,
    resetFormErrors,
    showFormErrors,
    apiFetch,
} from "./app";

document.addEventListener("DOMContentLoaded", function () {
    updateInputImage(6144);

    initHandlers();

    controlChangesGeneralNotifications();
});

function initHandlers() {
    document
        .getElementById("user-profile-form")
        .addEventListener("submit", submitUserProfileForm);
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
    const notificationTypes = document.querySelectorAll(".notification-type:checked");
    const notificationTypesCheckedValues = Array.from(notificationTypes).map(
        (checkbox) => checkbox.value
    );

    formData.append('notification_types', JSON.stringify(notificationTypesCheckedValues));

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
function controlChangesGeneralNotifications() {
    // Encuentra el checkbox "Notificaciones Generales"
    let generalNotificationsCheckbox = document.querySelector(
        "#general_notifications_allowed"
    );

    // Encuentra los otros checkboxes
    let otherCheckboxes = document.querySelectorAll(".notification-type");

    // Escucha los eventos de cambio en el checkbox "Notificaciones Generales"
    generalNotificationsCheckbox.addEventListener("change", function () {
        // Marca o desmarca los otros checkboxes para que coincidan con el estado del checkbox "Notificaciones Generales"
        otherCheckboxes.forEach(function (checkbox) {
            checkbox.checked = generalNotificationsCheckbox.checked;
        });
    });

    // Añade un escuchador de eventos a cada checkbox de tipo
    otherCheckboxes.forEach(function (checkbox) {
        checkbox.addEventListener("change", function () {
            // Si el checkbox de tipo está marcado, marca también el checkbox "Notificaciones Generales"
            if (checkbox.checked) {
                generalNotificationsCheckbox.checked = true;
            }
        });
    });
}
