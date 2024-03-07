import { heroicon } from "../heroicons.js";
import { hideModal, showModal, showModalConfirmation } from "../modal_handler";
import {
    showFormErrors,
    resetFormErrors,
    getMultipleTomSelectInstance,
    getLiveSearchTomSelectInstance,
    apiFetch,
    getFilterHtml,
    getOptionsSelectedTomSelectInstance
} from "../app.js";
import {
    controlsPagination,
    updateArrayRecords,
    updatePaginationInfo,
    tabulatorBaseConfig,
    controlsSearch,
    formatDateTime,
} from "../tabulator_handler";
import { TabulatorFull as Tabulator } from "tabulator-tables";
import { showToast } from "../toast.js";

const endPointGeneralNotificationsTable =
    "/notifications/general/get_list_general_notifications";
const endPointListUsersViewsGeneralNotificationTable =
    "/notifications/general/get_users_views_general_notification";

let generalNotificationsTable;
let listUsersViewsGeneralNotificationTable;

let selectedGeneralNotifications = [];

let tomSelectRoles;
let tomSelectUsers;

let tomSelectNotificationTypesFilter;

let filters = [];

document.addEventListener("DOMContentLoaded", function () {
    initHandlers();
    initializeGeneralNotificationsTable();
    controlsPagination(generalNotificationsTable, "notification-general-table");
    controlsSearch(
        generalNotificationsTable,
        endPointGeneralNotificationsTable,
        "notification-general-table"
    );

    controlTypeDestination();

    tomSelectRoles = getMultipleTomSelectInstance("#roles");
    tomSelectNotificationTypesFilter = getMultipleTomSelectInstance(
        "#notification_types"
    );

    tomSelectUsers = getLiveSearchTomSelectInstance(
        "#users",
        "/users/list_users/search_users/",
        function (entry) {
            return {
                value: entry.uid,
                text: `${entry.first_name} ${entry.last_name}`,
            };
        }
    );

    apiFetch({
        url: "/users/list_users/get_user_roles",
        method: "GET",
    }).then((data) => {
        data.forEach((rol) => {
            tomSelectRoles.addOption({
                value: rol.uid,
                text: rol.name,
            });
        });
    });

    apiFetch({
        url: "/notifications/general/get_general_notification_types",
        method: "GET",
    }).then((data) => {
        data.forEach((type) => {
            tomSelectNotificationTypesFilter.addOption({
                value: type.uid,
                text: type.name,
            });
        });
    });
});

function initHandlers() {
    document
        .getElementById("add-notification-general-btn")
        .addEventListener("click", function () {
            newGeneralNotification();
        });

    document
        .getElementById("notification-general-form")
        .addEventListener("submit", submitFormGeneralNotificationModal);

    document
        .getElementById("delete-notification-general-btn")
        .addEventListener("click", () => {
            if (selectedGeneralNotifications.length) {
                showModalConfirmation(
                    "Eliminar notificaciones generales",
                    "¿Está seguro que desea eliminar las notificaciones generales seleccionadas?",
                    "delete_educational_program_types"
                ).then((resultado) => {
                    if (resultado) {
                        deleteGeneralNotifications();
                    }
                });
            } else {
                showToast(
                    "Debe seleccionar al menos una notificación",
                    "error"
                );
            }
        });

    document
        .getElementById("filter-general-notification-btn")
        .addEventListener("click", function () {
            openFiltersModal();
        });

    document
        .getElementById("filter-btn")
        .addEventListener("click", function () {
            filters = collectFilters();

            showFilters();
            hideModal("filter-general-notifications-modal");

            initializeGeneralNotificationsTable();
        });
}

function openFiltersModal() {
    showModal("filter-general-notifications-modal");

}

function showFilters() {
    let html = "";

    filters.forEach((filter) => {
        html += getFilterHtml(filter.filterKey, filter.name, filter.option);
    });

    document.getElementById("filters").innerHTML = html;

    // Agregamos los listeners de eliminación a los filtros
    document.querySelectorAll(".delete-filter-btn").forEach((deleteFilter) => {
        deleteFilter.addEventListener("click", (event) => {
            controlDeleteFilters(event.currentTarget);
        });
    });
}

/**
 * Recoge todos los filtros aplicados en el modal de filtros.
 * Obtiene los valores de los elementos de entrada y los añade al array
 * de filtros seleccionados.
 */
function collectFilters() {
    let selectedFilters = [];
    /**
     *
     * @param {*} name nombre del filtro
     * @param {*} value Valor correspondiente a la opción seleccionada
     * @param {*} option Opción seleccionada
     * @param {*} filterKey Id correspondiente al filtro y al campo input al que corresponde
     * @param {*} database_field Nombre del campo de la BD correspondiente al filtro
     *
     * Añade filtros al array
     */
    function addFilter(name, value, option, filterKey, database_field = "") {
        if (value && value !== "") {
            selectedFilters.push({
                name,
                value,
                option,
                filterKey,
                database_field,
            });
        }
    }

    const startDateFilter = document.getElementById("start_date_filter").value;
    addFilter(
        "Fecha de inicio",
        startDateFilter,
        formatDateTime(startDateFilter),
        "start_date_filter",
        "start_date",
        "date"
    );

    const endDateFilter = document.getElementById("end_date_filter").value;
    addFilter(
        "Fecha de fin",
        endDateFilter,
        formatDateTime(endDateFilter),
        "end_date_filter",
        "end_date",
        "date"
    );

    // Collect values from TomSelects
    if (tomSelectNotificationTypesFilter) {
        const notificationTypes = tomSelectNotificationTypesFilter.getValue();

        const selectedNotificationTypesLabel =
            getOptionsSelectedTomSelectInstance(tomSelectNotificationTypesFilter);

        if (notificationTypes.length)
            addFilter("Tipos de notificación", notificationTypes, selectedNotificationTypesLabel, "notification_types", "notification_types", "notifications");
    }

    return selectedFilters;
}

/**
 * Maneja el evento de clic para eliminar un filtro específico.
 * Cuando se hace clic en un botón con la clase 'delete-filter-btn',
 * este elimina el filtro correspondiente del array 'filters' y actualiza
 * la visualización y la tabla de cursos.
 */
function controlDeleteFilters(deleteBtn) {
    const filterKey = deleteBtn.getAttribute("data-filter-key");

    let removedFilters = filters.filter((filter) => filter.filterKey === filterKey);

    removedFilters.forEach((removedFilter) => {
       document.getElementById(removedFilter.filterKey).value = "";

        if(removedFilter.filterKey === "notification_types") {
            tomSelectNotificationTypesFilter.clear();
        }
    });

    filters = filters.filter((filter) => filter.filterKey !== filterKey);
    document.getElementById(filterKey).value = "";

    showFilters();
    initializeGeneralNotificationsTable();
}

/**
 * controlTypeDestination - Función para controlar la visibilidad de los campos de destino
 * basados en el tipo de destino seleccionado ("ROLES" o "USERS").
 *
 * Escucha los cambios en el selector con el ID "selector-type-destination" y muestra
 * u oculta los campos relevantes de destino en función de la opción seleccionada.
 *
 * Los campos de destino se encuentran en divs con los IDs "destination-roles" y "destination-users",
 * y son inicialmente ocultados mediante la clase "no-visible".
 */
function controlTypeDestination() {
    const selector = document.getElementById("type");
    const destinationRoles = document.getElementById("destination-roles");
    const destinationUsers = document.getElementById("destination-users");

    selector.addEventListener("change", function () {
        const selectedValue = this.value;

        destinationRoles.classList.add("no-visible");
        destinationUsers.classList.add("no-visible");

        if (selectedValue === "ROLES") {
            destinationRoles.classList.remove("no-visible");
        } else if (selectedValue === "USERS") {
            destinationUsers.classList.remove("no-visible");
        }
    });
}

function switchSelectorDestination(type) {
    const destinationRoles = document.getElementById("destination-roles");
    const destinationUsers = document.getElementById("destination-users");
    destinationRoles.classList.add("no-visible");
    destinationUsers.classList.add("no-visible");

    if (type === "ROLES") {
        destinationRoles.classList.remove("no-visible");
    } else if (type === "USERS") {
        destinationUsers.classList.remove("no-visible");
    }
}

function newGeneralNotification() {
    resetModal("notification-general-form");
    showModal(
        "notification-general-modal",
        "Añade un nueva notificación general"
    );
}

function initializeListUserViewsGeneralNotificationsTable(uid) {
    if (listUsersViewsGeneralNotificationTable)
        listUsersViewsGeneralNotificationTable.destroy();

    const columns = [
        { title: "Nombre", field: "first_name", widthGrow: 3 },
        { title: "Apellidos", field: "last_name", widthGrow: 4 },
        { title: "Email", field: "email", widthGrow: 3 },
        { title: "Tipo", field: "general_notification_type", widthGrow: 3 },
        {
            title: "Fecha",
            field: "view_date",
            widthGrow: 2,
            formatter: function (cell, formatterParams, onRendered) {
                const isoDate = cell.getValue();
                if (!isoDate) return "";
                return formatDateTime(isoDate);
            },
        },
    ];

    listUsersViewsGeneralNotificationTable = new Tabulator(
        "#list-user-views-general-notification-table",
        {
            ajaxURL: endPointListUsersViewsGeneralNotificationTable + "/" + uid,
            ...tabulatorBaseConfig,
            ajaxResponse: function (url, params, response) {
                updatePaginationInfo(
                    listUsersViewsGeneralNotificationTable,
                    response,
                    "list-user-views-general-notification-table"
                );

                return {
                    last_page: response.last_page,
                    data: response.data,
                };
            },
            columns: columns,
        }
    );
}

function initializeGeneralNotificationsTable() {
    const columns = [
        {
            title: '<div class="checkbox-cell"><input type="checkbox" id="select-all-checkbox"/></div>',
            headerClick: function (e) {
                const selectAllCheckbox = e.target;
                if (selectAllCheckbox.type === "checkbox") {
                    // Asegúrate de que el clic fue en el checkbox
                    generalNotificationsTable.getRows().forEach((row) => {
                        const cell = row.getCell("select");
                        const checkbox = cell
                            .getElement()
                            .querySelector('input[type="checkbox"]');
                        checkbox.checked = selectAllCheckbox.checked;
                        selectedGeneralNotifications = updateArrayRecords(
                            checkbox,
                            row.getData(),
                            selectedGeneralNotifications
                        );
                    });
                }
            },
            field: "select",
            formatter: function (cell, formatterParams, onRendered) {
                const uid = cell.getRow().getData().uid;
                return `<input type="checkbox" data-uid="${uid}"/>`;
            },
            width: 60,
            cssClass: "checkbox-cell",
            cellClick: function (e, cell) {
                // Lógica cuando se hace clic en la celda
                const checkbox = e.target;
                const rowData = cell.getRow().getData();

                selectedGeneralNotifications = updateArrayRecords(
                    checkbox,
                    rowData,
                    selectedGeneralNotifications
                );
            },
            headerSort: false,
            //widthGrow: 1,
        },
        { title: "Titulo", field: "title", widthGrow: 2 },
        {
            title: "Tipo",
            field: "general_notification_type_name",
            widthGrow: 2,
        },
        { title: "Descripción", field: "description", widthGrow: 2 },
        {
            title: "Fecha de inicio",
            field: "start_date",
            widthGrow: 2,
            formatter: function (cell, formatterParams, onRendered) {
                const isoDate = cell.getValue();
                if (!isoDate) return "";
                return formatDateTime(isoDate);
            },
        },
        {
            title: "Fecha de fin",
            field: "end_date",
            widthGrow: 2,
            formatter: function (cell, formatterParams, onRendered) {
                const isoDate = cell.getValue();
                if (!isoDate) return "";
                return formatDateTime(isoDate);
            },
        },
        {
            title: "Destino",
            widthGrow: 2,
            formatter: function (cell, formatterParams, onRendered) {
                const data = cell.getRow().getData();
                if (data.type === "ALL_USERS") {
                    return "Todos los usuarios";
                } else if (data.type === "ROLES") {
                    const rolesString = data.roles
                        .map((rol) => rol.name)
                        .join(", ");
                    return rolesString;
                } else if (data.type === "USERS") {
                    return "Usuarios concretos";
                }
            },
        },
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
                const generalNotificationClicked = cell.getRow().getData();
                loadGeneralNotificationModal(generalNotificationClicked.uid);
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
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
                const generalNotificationClicked = cell.getRow().getData();

                loadUserViewsGeneralNotification(
                    generalNotificationClicked.uid
                );
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
    ];

    generalNotificationsTable = new Tabulator("#notification-general-table", {
        ajaxURL: endPointGeneralNotificationsTable,
        ...tabulatorBaseConfig,
        ajaxParams: {
            filters: {
                ...filters,
            },
        },
        ajaxResponse: function (url, params, response) {
            updatePaginationInfo(
                generalNotificationsTable,
                response,
                "notification-general-table"
            );

            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });
}

async function loadUserViewsGeneralNotification(uid) {
    initializeListUserViewsGeneralNotificationsTable(uid);

    controlsSearch(
        listUsersViewsGeneralNotificationTable,
        endPointListUsersViewsGeneralNotificationTable + "/" + uid,
        "list-user-views-general-notification-table"
    );

    controlsPagination(
        listUsersViewsGeneralNotificationTable,
        "list-user-views-general-notification-table"
    );

    showModal("list-users-views-general-notification-modal");
}

async function loadGeneralNotificationModal(uid) {
    const params = {
        url: `/notifications/general/get_general_notification/${uid}`,
        method: "GET",
        loader: true,
    };

    apiFetch(params).then((data) => {
        fillFormGeneralNotificationModal(data);
        showModal("notification-general-modal", "Editar notificación general");
    });
}

function fillFormGeneralNotificationModal(general_notification) {
    resetModal("notification-general-form");
    switchSelectorDestination(general_notification.type);

    if (general_notification.roles) {
        general_notification.roles.forEach((rol) => {
            tomSelectRoles.addItem(rol.uid);
        });
    }

    if (general_notification.users) {
        general_notification.users.forEach((user) => {
            tomSelectUsers.addOption({
                value: user.uid,
                text: user.first_name,
            });
            tomSelectUsers.addItem(user.uid);
        });
    }

    document.getElementById("type").value = general_notification.type;
    document.getElementById("title").value = general_notification.title;
    document.getElementById("description").value =
        general_notification.description;
    document.getElementById("notification_general_uid").value =
        general_notification.uid;

    document.getElementById("start_date").value =
        general_notification.start_date;
    document.getElementById("end_date").value = general_notification.end_date;
}

function submitFormGeneralNotificationModal() {
    const formData = new FormData(this);
    const roles = tomSelectRoles.items;
    formData.append("tags", JSON.stringify(roles));

    resetFormErrors("notification-general-form");

    const params = {
        url: "/notifications/general/save_general_notifications",
        method: "POST",
        body: formData,
        loader: true,
        toast: true,
    };

    apiFetch(params)
        .then(() => {
            generalNotificationsTable.replaceData(
                endPointGeneralNotificationsTable
            );
            hideModal("notification-general-modal");
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

function resetModal() {
    const form = document.getElementById("notification-general-form");
    form.reset();
    tomSelectRoles.clear();
    tomSelectUsers.clear();
    document.getElementById("notification_general_uid").value = "";

    resetFormErrors("notification-general-form");
}

async function deleteGeneralNotifications() {
    const params = {
        url: "/notifications/general/delete_general_notifications",
        method: "DELETE",
        body: { uids: selectedGeneralNotifications.map((type) => type.uid) },
        stringify: true,
        loader: true,
        toast: true,
    };

    apiFetch(params).then(() => {
        generalNotificationsTable.replaceData(
            endPointGeneralNotificationsTable
        );
        selectedGeneralNotifications = [];
    });
}
