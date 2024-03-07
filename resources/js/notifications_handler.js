import { showModal } from "./modal_handler";
import { apiFetch, formatDateTime } from "./app";

document.addEventListener("DOMContentLoaded", function () {
    controlNotification();

    // Configura el botón de la campana para controlar la visibilidad de las notificaciones
    const bellButton = document.getElementById("bell-btn");
    const notificationBox = document.getElementById("notification-box");

    // Evento para alternar la visibilidad de notificationBox al hacer clic en bellButton
    bellButton.addEventListener("click", function (event) {
        event.stopPropagation();
        toggleNotification();
    });

    // Evento para cerrar notificationBox si se hace clic fuera de él
    document.addEventListener("click", function (event) {
        if (
            !notificationBox.contains(event.target) &&
            !notificationBox.classList.contains("hidden")
        ) {
            notificationBox.classList.add("hidden");
        }
    });
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
    } else if (notificationType === "course_status") {
        apiFetch({
            ...params,
            url:
                "/notifications/notifications_statuses_courses/get_notifications_statuses_courses/" +
                notificationUid,
        }).then((data) => {
            markReadNotification(notificationElement);
            openNotificationChangeStatusCourse(data);
        });
    }
}

// Abre un modal con los detalles de la notificación
function openNotification(notification) {
    document.getElementById("notification-description").innerText =
        notification.description;

    showModal("notification-info-modal", notification.title);
}

function openNotificationChangeStatusCourse(notification) {
    document.getElementById(
        "notification-change-course-status-name"
    ).innerText = notification.course.title;

    document.getElementById(
        "notification-change-course-status-status"
    ).innerText = notification.status.name;

    document.getElementById(
        "notification-change-course-status-date"
    ).innerText = formatDateTime(notification.date);

    document.getElementById("notification-change-course-status-url").href =
        "/learning_objects/courses?uid=" + notification.course.uid;

    showModal("notification-change-course-status-modal", "Cambio de estado");
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
