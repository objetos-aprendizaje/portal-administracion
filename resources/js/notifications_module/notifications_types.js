import { heroicon } from "../heroicons.js";
import { hideModal, showModal, showModalConfirmation } from "../modal_handler";
import {
    resetFormErrors,
    apiFetch,
    showFormErrors
} from "../app.js";
import {
    controlsPagination,
    updateArrayRecords,
    tabulatorBaseConfig,
    updatePaginationInfo,
    controlsSearch,
} from "../tabulator_handler";
import { TabulatorFull as Tabulator } from "tabulator-tables";
import { showToast } from "../toast.js";

const endPointTable = "/notifications/notifications_types/get_list_notification_types";
let notificationTypesTable;

let selectedNotificationTypes = [];

document.addEventListener("DOMContentLoaded", function () {
    initHandlers();

    initializeNotificationTypesTable();
});

function initHandlers() {
    document
        .getElementById("add-notification-type-btn")
        .addEventListener("click", function () {
            newNotificationType();
        });

    document
        .getElementById("delete-notification-type-btn")
        .addEventListener("click", () => {
            if (selectedNotificationTypes.length) {
                showModalConfirmation(
                    "Eliminar tipos de notificación",
                    "¿Está seguro que desea eliminar los tipos de notificación seleccionados?",
                    "delete_notifications_types"
                ).then((resultado) => {
                    if (resultado) {
                        deleteNotificationTypes();
                    }
                });
            } else {
                showToast(
                    "Debe seleccionar al menos un tipo de notificación",
                    "error"
                );
            }
        });

    document
        .getElementById("notification-type-form")
        .addEventListener("submit", submitFormNotificationTypeModal);
}

function newNotificationType() {
    resetModal();
    showModal("notification-type-modal", "Añade un nuevo tipo de notificación");
}

function initializeNotificationTypesTable() {
    const columns = [
        {
            title: '<div class="checkbox-cell"><input type="checkbox" id="select-all-checkbox"/></div>',
            headerClick: function (e) {
                const selectAllCheckbox = e.target;
                if (selectAllCheckbox.type === "checkbox") {
                    // Asegúrate de que el clic fue en el checkbox
                    notificationTypesTable.getRows().forEach((row) => {
                        const cell = row.getCell("select");
                        const checkbox = cell
                            .getElement()
                            .querySelector('input[type="checkbox"]');
                        checkbox.checked = selectAllCheckbox.checked;
                        selectedNotificationTypes = updateArrayRecords(
                            checkbox,
                            row.getData(),
                            selectedNotificationTypes
                        );
                    });
                }
            },
            field: "select",
            formatter: function (cell, formatterParams, onRendered) {
                const uid = cell.getRow().getData().uid;
                return `<input type="checkbox" data-uid="${uid}"/>`;
            },
            cssClass: "checkbox-cell",
            cellClick: function (e, cell) {
                // Lógica cuando se hace clic en la celda
                const checkbox = e.target;
                const rowData = cell.getRow().getData();

                selectedNotificationTypes = updateArrayRecords(
                    checkbox,
                    rowData,
                    selectedNotificationTypes
                );
            },
            headerSort: false,
            width: 60,
        },
        { title: "Nombre", field: "name"},
        { title: "Descripción", field: "description"},
        {
            title: "",
            field: "actions",
            formatter: function (cell, formatterParams, onRendered) {
                return `
                    <button type="button" class='btn action-btn'>${heroicon(
                        "pencil-square",
                        "outline"
                    )}</button>
                `;
            },
            cellClick: function (e, cell) {
                e.preventDefault();
                const notificationTypeClicked = cell.getRow().getData();
                loadNotificationTypeModal(notificationTypeClicked.uid);
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
    ];

    notificationTypesTable = new Tabulator("#notification-types-table", {
        ajaxURL: endPointTable,
        ...tabulatorBaseConfig,
        ajaxResponse: function (url, params, response) {
            updatePaginationInfo(
                notificationTypesTable,
                response,
                "notification-types-table"
            );

            selectedNotificationTypes = [];

            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });

    controlsSearch(notificationTypesTable, endPointTable, "notification-types-table");
    controlsPagination(notificationTypesTable, "notification-types-table");
}

async function loadNotificationTypeModal(uid) {
    const params = {
        url: `/notifications/notifications_types/get_notification_type/${uid}`,
        method: "GET",
        loader: true,
    };

    apiFetch(params).then((data) => {
        fillFormNotificationTypeModal(data);
        showModal("notification-type-modal", "Edita el tipo de notificación");
    });
}

function fillFormNotificationTypeModal(notification_type) {
    document.getElementById("name").value = notification_type.name;
    document.getElementById("notification_type_uid").value = notification_type.uid;
    document.getElementById("description").value = notification_type.description;
}

/**
 * Envía el formulario del modal de tipo de notificación.
 * Realiza una petición POST al servidor con los datos del formulario.
 */
function submitFormNotificationTypeModal() {
    const formData = new FormData(this);
    const params = {
        url: "/notifications/notifications_types/save_notification_type",
        method: "POST",
        body: formData,
        loader: true,
        toast: true,
    };

    resetFormErrors("notification-type-form");
    apiFetch(params).then(() => {
        resetModal();
        notificationTypesTable.replaceData(endPointTable);
        hideModal("notification-type-modal");
    }).catch((data) => {
        showFormErrors(data.errors);
    });
}

function resetModal() {
    const form = document.getElementById("notification-type-form");
    form.reset();
    document.getElementById("notification_type_uid").value = "";
    document.getElementById("description").value = "";
    resetFormErrors();
}

/**
 * Elimina tipos de notificación seleccionados.
 * Realiza una petición DELETE al servidor y actualiza la tabla si tiene éxito.
 */
async function deleteNotificationTypes() {
    const params = {
        url: "/notifications/notifications_types/delete_notifications_types",
        method: "DELETE",
        body: {
            uids: selectedNotificationTypes.map((type) => type.uid),
        },
        loader: true,
        toast: true,
        stringify: true,
    };

    apiFetch(params).then(() => {
        notificationTypesTable.replaceData(endPointTable);
    });
}
