import { showModal } from "./modal_handler";
import { apiFetch, formatDateTime } from "./app";

document.addEventListener("DOMContentLoaded", function () {
    controlNotification();

    // Configura el botón de la campana para controlar la visibilidad de las notificaciones
    const bellButton = document.getElementById("bell-btn");
    const notificationBox = document.getElementById("notification-box");

    // Evento para alternar la visibilidad de notificationBox al hacer clic en bellButton
    if (bellButton) {
        bellButton.addEventListener("click", function (event) {
            event.stopPropagation();
            toggleNotification();
        });
    }

    // Evento para cerrar notificationBox si se hace clic fuera de él
    if (notificationBox) {
        document.addEventListener("click", function (event) {
            if (
                !notificationBox.contains(event.target) &&
                !notificationBox.classList.contains("hidden")
            ) {
                notificationBox.classList.add("hidden");
            }
        });
    }
});

// Alterna la visibilidad del contenedor de notificaciones.
function toggleNotification() {
    const notificationBox = document.querySelector("#notification-box");

    if (notificationBox) {
        notificationBox.classList.toggle("hidden");
    }
}

// Configura el comportamiento al hacer clic en cada notificación
function controlNotification() {
    const notificationElements = document.querySelectorAll(".notification");

    notificationElements.forEach(function (notificationElement) {
        notificationElement.addEventListener("click", function (event) {
            loadNotification(notificationElement);
        });
    });
}

// Carga los detalles de una notificación específica
function loadNotification(notificationElement) {
    const notificationUid = notificationElement.dataset.notification_uid;
    const notificationType = notificationElement.dataset.notification_type;

    const params = {
        method: "GET",
        loader: true,
    };

    // Dependiendo del tipo de notificación, se realiza una petición diferente
    if (notificationType === "general") {
        apiFetch({
            ...params,
            url:
                "/notifications/general/get_general_notification_user/" +
                notificationUid,
        }).then((data) => {
            markReadNotification(notificationElement);
            openNotification(data);
        });
    } else if (notificationType === "automatic") {
        apiFetch({
            ...params,
            url:
                "/notifications/notifications_statuses_courses/get_general_notification_automatic/" +
                notificationUid,
        }).then((data) => {
            markReadNotification(notificationElement);
            openNotificationAutomatic(data);
        });
    }
}

function openNotificationAutomatic(notificationAutomatic) {
    document.getElementById("entity-btn-container").classList.remove("hidden");
    document.getElementById("notification-description").innerHTML =
        notificationAutomatic.description;

    const code = notificationAutomatic.automatic_notification_type.code;

    // Configuraciones para diferentes tipos de notificaciones automáticas
    const notificationConfig = {
        COURSE_ENROLLMENT_TEACHER_COMMUNICATIONS: {
            url:
                "/learning_objects/courses?uid=" +
                notificationAutomatic.entity_uid,
            label: "Ir al curso",
        },
        NEW_COURSES_NOTIFICATIONS_MANAGEMENTS: {
            url: "/learning_objects/courses",
            label: "Ir a los cursos",
        },
        NEW_EDUCATIONAL_PROGRAMS_NOTIFICATIONS_MANAGEMENTS: {
            url: "/learning_objects/educational_programs",
            label: "Ir a los programas",
        },
        CHANGE_STATUS_EDUCATIONAL_PROGRAM: {
            url: "/learning_objects/educational_programs",
            label: "Ir a los programas",
        }
    };

    const config = notificationConfig[code];

    if (config) {
        document.getElementById("entity-url").href = config.url;
        document.getElementById("entity-btn-label").innerText = config.label;
    }

    showModal("notification-info-modal", notificationAutomatic.title);
}

// Abre un modal con los detalles de la notificación
function openNotification(notification) {
    document.getElementById("entity-btn-container").classList.add("hidden");
    document.getElementById("notification-description").innerText =
        notification.description;

    showModal("notification-info-modal", notification.title);
}

// Marca una notificación como leída y actualiza el indicador visual
function markReadNotification(notificationDiv) {
    const notReadDiv = notificationDiv.querySelector(".not-read");

    if (notReadDiv) notReadDiv.remove();

    const unreadNotifications = checkUnreadNotifications();

    if (!unreadNotifications) {
        document.getElementById("notification-dot").classList.add("hidden");
    }
}

// Verifica si hay notificaciones no leídas restantes
function checkUnreadNotifications() {
    const notificationBox = document.getElementById("notification-box");

    const notReadDiv = notificationBox.querySelector(".not-read");

    return notReadDiv ? true : false;
}
