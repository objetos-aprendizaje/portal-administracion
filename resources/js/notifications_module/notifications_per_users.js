import { heroicon } from "../heroicons.js";
import { hideModal, showModal, showModalConfirmation } from "../modal_handler";
import {
    controlsPagination,
    tabulatorBaseConfig,
    updatePaginationInfo,
    controlsSearch,
    formatDateTime,
} from "../tabulator_handler";
import { TabulatorFull as Tabulator } from "tabulator-tables";

const endPointTable = "/notifications/notifications_per_users/get_list_users";
let notificationPerUsersTable;
let notificationsPerUserTable;

let selectedNotificationPerUsers = [];

document.addEventListener("DOMContentLoaded", function () {
    initializeNotificationPerUsersTable();

});

function initializeNotificationPerUsersTable() {
    const columns = [
        { title: "Nombre", field: "first_name"},
        { title: "Apellidos", field: "last_name"},
        {
            title: "",
            field: "actions",
            formatter: function (cell, formatterParams, onRendered) {
                return `
                    <button type="button" class='btn action-btn'>${heroicon(
                        "eye",
                        "outline"
                    )}</button>
                `;
            },
            cellClick: function (e, cell) {
                e.preventDefault();
                const notificationPerUsersClicked = cell.getRow().getData();
                fillFormNotificationsPerUsersModal(notificationPerUsersClicked.uid,notificationPerUsersClicked.first_name,notificationPerUsersClicked.last_name);
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
    ];

    notificationPerUsersTable = new Tabulator("#notification-per-users-table", {
        ajaxURL: endPointTable,
        ...tabulatorBaseConfig,
        ajaxResponse: function (url, params, response) {
             updatePaginationInfo(
                notificationPerUsersTable,
                response,
                "notification-per-users-table"
            );

            selectedNotificationPerUsers = [];

            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });

    controlsSearch(notificationPerUsersTable, endPointTable, "notification-per-users-table");
    controlsPagination(notificationPerUsersTable, "notification-per-users-table");
}

async function fillFormNotificationsPerUsersModal(uid,last_name,first_name) {

    if (first_name == null){
        first_name  = "";
    }
    if (last_name == null){
        last_name  = "";
    }

    showModal("notifications-per-user-modal", `Notificaciones vistas por ${first_name} ${last_name}`);

    const columns = [
        {
            title: "Fecha visto",
            field: "pivot.view_date",
            widthGrow:1,
            formatter: function (cell, formatterParams, onRendered) {
                const isoDate = cell.getValue();
                if (!isoDate) return "";
                return formatDateTime(isoDate);
            },
        },
        { title: "Título", field: "title", withGrow: 3 },
        { title: "Descripción", field: "description", withGrow: 3}
    ];

    const endPointTable = "/notifications/notifications_per_users/get_notifications/"+uid;

    notificationsPerUserTable = new Tabulator("#notifications-per-user-table", {
        ajaxURL: endPointTable,
        ...tabulatorBaseConfig,
        ajaxResponse: function (url, params, response) {
             updatePaginationInfo(
                notificationsPerUserTable,
                response,
                "notifications-per-user-table"
            );

            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });
    controlsPagination(notificationsPerUserTable, "notifications-per-user-table");
    controlsSearch(notificationsPerUserTable, endPointTable, "notifications-per-user-table");

    // Luego, puedes llamar a esta función cuando sea necesario, por ejemplo, en el evento click de un botón.
    var buttons = document.getElementsByClassName("search-table-btn");
    for (var i = 0; i < buttons.length; i++) {
        buttons[i].addEventListener("click", limpiarBusqueda);
    }
}
function limpiarBusqueda() {
    var buttons = document.getElementsByClassName("search-table");
    for (var i = 0; i < buttons.length; i++) {
        buttons[i].value = "";
    }
}
