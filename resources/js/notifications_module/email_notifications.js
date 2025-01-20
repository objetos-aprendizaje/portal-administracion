import {
    controlsPagination,
    tabulatorBaseConfig,
    updatePaginationInfo,
    formatDateTime,
    controlsSearch,
    updateArrayRecords,
} from "../tabulator_handler";
import { heroicon } from "../heroicons";
import { TabulatorFull as Tabulator } from "tabulator-tables";
import { hideModal, showModal, showModalConfirmation } from "../modal_handler";
import TomSelect from "tom-select";
import {
    showFormErrors,
    resetFormErrors,
    getMultipleTomSelectInstance,
    toggleFormFields,
    apiFetch,
    instanceFlatpickr,
    getFlatpickrDateRange,
    getFlatpickrDateRangeSql,
    getOptionsSelectedTomSelectInstance,
    getLiveSearchTomSelectInstance,
} from "../app.js";
import { showToast } from "../toast.js";

const endPointTable = "/notifications/email/get_list_email_notifications";

let emailNotificationsTable = null;

let tomSelectRoles;
let tomSelectUsers;
let selectedEmailNotifications = [];
let flatpickrNotificationDate;
let tomSelectNotificationTypesFilter;
let filters = [];
let tomSelectRolesFilter;
let tomSelectUsersFilter;

document.addEventListener("DOMContentLoaded", function () {
    initHandlers();
    initializeEmailNotificationsTable();
    controlTypeDestination();
    initFlatPickr();
    controlsSearch(
        emailNotificationsTable,
        endPointTable,
        "notification-email-table"
    );
    controlsPagination(emailNotificationsTable, "notification-email-table");
    tomSelectNotificationTypesFilter = getMultipleTomSelectInstance(
        "#notification_types"
    );
    tomSelectRolesFilter = getMultipleTomSelectInstance("#roles-filter");
    tomSelectUsersFilter = getLiveSearchTomSelectInstance(
        "#users-filter",
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
            tomSelectRolesFilter.addOption({
                value: rol.uid,
                text: rol.name,
            });
        });
    });

    initTomSelectUsers();
});

function initHandlers() {
    document
        .getElementById("add-notification-email-btn")
        .addEventListener("click", function () {
            newEmailNotification();
        });

    document
        .getElementById("filter-notification-email-btn")
        .addEventListener("click", function () {
            openFiltersModal();
        });

    document
        .getElementById("filter-btn")
        .addEventListener("click", function () {
            controlSaveHandlerFilters();
        });

    document
        .getElementById("delete-all-filters")
        .addEventListener("click", function () {
            resetFilters();
        });

    tomSelectRoles = getMultipleTomSelectInstance("#roles");

    // Cargamos los roles
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

    document
        .getElementById("delete-notification-email-btn")
        .addEventListener("click", () => {
            if (selectedEmailNotifications.length) {
                showModalConfirmation(
                    "Eliminar notificaciones por email",
                    "¿Está seguro que desea eliminar las notificaciones seleccionadas?",
                    "delete_educational_program_types"
                ).then((resultado) => {
                    if (resultado) {
                        deleteEmailNotifications();
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
        .getElementById("notification-email-form")
        .addEventListener("submit", submitFormEmailNotificationModal);
}

function openFiltersModal() {
    showModal("filter-notification-email-modal");
}
function initFlatPickr() {
    flatpickrNotificationDate = instanceFlatpickr("date_email_notifications");
}

/**
 * Maneja el evento de clic en el botón para aplicar los filtros.
 * Recoge los filtros del modal, los muestra en la interfaz y vuelve a inicializar
 * la tabla de cursos con los nuevos filtros aplicados.
 */
function controlSaveHandlerFilters() {
    filters = collectFilters();

    showFilters();
    hideModal("filter-notification-email-modal");

    initializeEmailNotificationsTable();
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
     * @param {*} filterType Tipo de filtro
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

    if (flatpickrNotificationDate.selectedDates.length)
        addFilter(
            "Fecha de notificacion",
            getFlatpickrDateRangeSql(flatpickrNotificationDate),
            getFlatpickrDateRange(flatpickrNotificationDate),
            "date_email_notifications",
            "send_date"
        );

    const stateEmailNotification = document.querySelector(
        "#state_email_notification"
    );
    if (stateEmailNotification.value.length) {
        let statusNotificationLabel = "";
        if (stateEmailNotification.value == "PENDING") {
            statusNotificationLabel = "Pendiente";
        } else if (stateEmailNotification.value == "SENT") {
            statusNotificationLabel = "Enviado";
        } else if (stateEmailNotification.value == "FAILED") {
            statusNotificationLabel = "Error";
        }

        addFilter(
            "Estado de la notificación",
            stateEmailNotification.value,
            statusNotificationLabel,
            "state_email_notification",
            "status"
        );
    }
    // Collect values from TomSelects
    if (tomSelectNotificationTypesFilter) {
        const notificationTypes = tomSelectNotificationTypesFilter.getValue();

        const selectedNotificationTypesLabel =
            getOptionsSelectedTomSelectInstance(
                tomSelectNotificationTypesFilter
            );

        if (notificationTypes.length)
            addFilter(
                "Tipos de notificación",
                notificationTypes,
                selectedNotificationTypesLabel,
                "notification_types",
                "notification_types",
            );
    }
    if (tomSelectRolesFilter) {
        const rolesFilter = tomSelectRolesFilter.getValue();

        const selectedRolesFilterLabel =
            getOptionsSelectedTomSelectInstance(tomSelectRolesFilter);

        if (rolesFilter.length)
            addFilter(
                "Roles",
                rolesFilter,
                selectedRolesFilterLabel,
                "roles-filter",
                "roles"
            );
    }

    if (tomSelectUsersFilter) {
        const usersFilter = tomSelectUsersFilter.getValue();

        const selectedUsersFilterLabel =
            getOptionsSelectedTomSelectInstance(tomSelectUsersFilter);

        if (usersFilter.length)
            addFilter(
                "usuarios",
                usersFilter,
                selectedUsersFilterLabel,
                "users-filter",
                "users"
            );
    }

    return selectedFilters;
}

/**
 * Muestra los filtros aplicados en la interfaz de usuario.
 * Recorre el array de 'filters' y genera el HTML para cada filtro,
 * permitiendo su visualización y posterior eliminación. Además muestra u oculta
 * el botón de eliminación de filtros
 */
function showFilters() {
    // Eliminamos todos los filtros
    const currentFilters = document.querySelectorAll(".filter");

    // Recorre cada elemento y lo elimina
    currentFilters.forEach(function (filter) {
        filter.remove();
    });

    filters.forEach((filter) => {
        // Crea un nuevo div
        const newDiv = document.createElement("div");

        // Agrega la clase 'filter' al div
        newDiv.classList.add("filter");

        // Establece el HTML del nuevo div
        newDiv.innerHTML = `
            <div>${filter.name}: ${filter.option}</div>
            <button data-filter-key="${
                filter.filterKey
            }" class="delete-filter-btn">${heroicon(
            "x-mark",
            "outline"
        )}</button>
        `;

        // Agrega el nuevo div al div existente
        document.getElementById("filters").prepend(newDiv);
    });

    const deleteAllFiltersBtn = document.getElementById("delete-all-filters");

    if (filters.length == 0) deleteAllFiltersBtn.classList.add("hidden");
    else deleteAllFiltersBtn.classList.remove("hidden");

    // Agregamos los listeners de eliminación a los filtros
    document.querySelectorAll(".delete-filter-btn").forEach((deleteFilter) => {
        deleteFilter.addEventListener("click", (event) => {
            controlDeleteFilters(event.currentTarget);
        });
    });
}

function resetFilters() {
    filters = [];
    showFilters();
    initializeEmailNotificationsTable();

    flatpickrNotificationDate.clear();
    tomSelectNotificationTypesFilter.clear();
    tomSelectRolesFilter.clear();
    tomSelectUsersFilter.clear();

    document.getElementById("type-filter").value = "";
    document.getElementById("state_email_notification").value = "";

    document
        .querySelector("#destination-roles-filter")
        .classList.add("no-visible");
    document
        .querySelector("#destination-users-filter")
        .classList.add("no-visible");
}

function controlDeleteFilters(deleteBtn) {
    const filterKey = deleteBtn.getAttribute("data-filter-key");


    if (filterKey == "date_email_notifications") {
        flatpickrNotificationDate.clear();
    } else if (filterKey == "notification_types") {
        tomSelectNotificationTypesFilter.clear();
    } else if (filterKey == "roles-filter") {
        tomSelectRolesFilter.clear();
    } else if (filterKey == "users-filter") {
        tomSelectUsersFilter.clear();
    } else {
        document.getElementById(filterKey).value = "";
    }

    filters = filters.filter((filter) => filter.filterKey !== filterKey);
    document.getElementById(filterKey).value = "";
    document.getElementById("type-filter").value = "";

    showFilters();
    initializeEmailNotificationsTable();
}
function initTomSelectUsers() {
    tomSelectUsers = new TomSelect("#users", {
        plugins: {
            remove_button: {
                title: "Eliminar",
            },
        },
        search: true,
        create: false,
        load: function (query, callback) {
            const params = {
                url:
                    "/users/list_users/search_users/" +
                    encodeURIComponent(query),
                method: "GET",
            };

            apiFetch(params)
                .then((data) => {
                    if (data.length) {
                        const response = data.map((entry) => {
                            return {
                                value: entry.uid,
                                text: `${entry.first_name} ${entry.last_name} (${entry.email})`,
                            };
                        });
                        callback(response);
                    } else {
                        callback();
                    }
                })
                .catch(() => {
                    callback();
                });
        },
        render: {
            no_results: function (data, escape) {
                return '<div class="no-results">No se encontraron resultados</div>';
            },
        },
        onItemAdd: function () {
            this.control_input.value = "";
        },
    });
}

async function loadEmailNotificationModal(uid) {
    const params = {
        url: `/notifications/email/get_email_notification/${uid}`,
        method: "GET",
        loader: true,
    };

    apiFetch(params).then((data) => {
        fillFormEmailNotificationModal(data);
        showModal("notification-email-modal", "Notificación por email");
    });
}

function fillFormEmailNotificationModal(email_notification) {
    resetModal();
    switchSelectorDestination(email_notification.type);

    if (email_notification.roles) {
        email_notification.roles.forEach((rol) => {
            tomSelectRoles.addItem(rol.uid);
        });
    }

    if (email_notification.users) {
        email_notification.users.forEach((user) => {
            tomSelectUsers.addOption({
                value: user.uid,
                text: user.first_name,
            });
            tomSelectUsers.addItem(user.uid);
        });
    }

    document.getElementById("type").value = email_notification.type;
    document.getElementById("subject").value = email_notification.subject;
    document.getElementById("body").value = email_notification.body;
    document.getElementById("notification_email_uid").value =
        email_notification.uid;
    document.getElementById("send_date").value =
        email_notification.send_date.substring(0, 16);
    document.getElementById("notification_type_uid").value =
        email_notification.notification_type_uid;

    if (email_notification.status == 'SENT') switchBtnsModal("view");
    else switchBtnsModal("add");
}

/**
 * Alterna la visibilidad de dos grupos de botones en una modal.
 *
 * Esta función se encarga de controlar qué grupo de botones se muestra en una modal de notificaciones de correo electrónico.
 * Dependiendo de la acción proporcionada ('view' o 'add'), se ocultará un grupo de botones y se mostrará el otro.
 *
 */
function switchBtnsModal(action) {
    const btnsAdd = document.getElementById(
        "email-notification-modal-add-btns"
    );
    const btnsView = document.getElementById(
        "email-notification-modal-view-btns"
    );

    if (action === "view") {
        btnsAdd.style.display = "none";
        btnsView.style.display = "flex";

        toggleFormFields("notification-email-form", true);
        tomSelectRoles.disable();
        tomSelectUsers.disable();
    } else if (action === "add") {
        btnsView.style.display = "none";
        btnsAdd.style.display = "flex";
        toggleFormFields("notification-email-form", false);
        tomSelectRoles.enable();
        tomSelectUsers.enable();
    }
}

function newEmailNotification() {
    switchBtnsModal("add");
    resetModal();
    showModal("notification-email-modal", "Nueva notificación por email");
}

async function deleteEmailNotifications() {
    const params = {
        url: "/notifications/email/delete_email_notifications",
        method: "DELETE",
        body: {
            uids: selectedEmailNotifications.map((type) => type.uid),
        },
        stringify: true,
        toast: true,
        loader: true,
    };

    apiFetch(params).then(() => {
        emailNotificationsTable.replaceData(endPointTable);
    });
}

function initializeEmailNotificationsTable() {
    const columns = [
        {
            title: '<div class="checkbox-cell"><input type="checkbox" id="select-all-checkbox"/></div>',
            headerClick: function (e) {
                const selectAllCheckbox = e.target;
                if (selectAllCheckbox.type === "checkbox") {
                    // Asegúrate de que el clic fue en el checkbox
                    emailNotificationsTable.getRows().forEach((row) => {
                        const cell = row.getCell("select");
                        const checkbox = cell
                            .getElement()
                            .querySelector('input[type="checkbox"]');

                        if (checkbox) {
                            checkbox.checked = selectAllCheckbox.checked;
                            selectedEmailNotifications = updateArrayRecords(
                                checkbox,
                                row.getData(),
                                selectedEmailNotifications
                            );
                        }
                    });
                }
            },
            field: "select",
            formatter: function (cell, formatterParams, onRendered) {
                const uid = cell.getRow().getData().uid;

                const sent = cell.getRow().getData().sent;

                if (!sent) return `<input type="checkbox" data-uid="${uid}"/>`;
            },
            width: 60,
            cssClass: "checkbox-cell",
            cellClick: function (e, cell) {
                // Lógica cuando se hace clic en la celda
                const checkbox = e.target;
                const rowData = cell.getRow().getData();

                selectedEmailNotifications = updateArrayRecords(
                    checkbox,
                    rowData,
                    selectedEmailNotifications
                );
            },
            headerSort: false,
        },
        {
            title: "Estado",
            field: "status",
            formatter: function (cell, formatterParams, onRendered) {
                const status = cell.getRow().getData().status;
                let statusLabel = "";
                let color = "";
                if (status == "PENDING") {
                    statusLabel = "Pendiente";
                    color = "#F4EBF0";
                } else if (status == "SENT") {
                    statusLabel = "Enviado";
                    color = "#EBF3F4";
                } else if (status == "FAILED") {
                    statusLabel = "Error";
                    color = "#FCE8E6";
                }

                return `
                    <div class="label-status" style="background-color: ${color}">${statusLabel}</div>
                `;
            },
            widthGrow: 3,
        },
        { title: "Asunto", field: "subject", widthGrow: 2 },
        { title: "Cuerpo", field: "body", widthGrow: 4 },
        {
            title: "Tipo",
            field: "notification_type_name",
            widthGrow: 2,
        },
        {
            title: "Fecha de envío",
            field: "send_date",
            widthGrow: 2,
            formatter: function (cell, formatterParams, onRendered) {
                const isoDate = cell.getValue();
                if (!isoDate) return "";
                return formatDateTime(isoDate);
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
                const emailNotificationClicked = cell.getRow().getData();
                loadEmailNotificationModal(emailNotificationClicked.uid);
            },
            cssClass: "text-center",
            headerSort: false,
            width: 60,
            resizable: false,
        },
    ];

    emailNotificationsTable = new Tabulator("#notification-email-table", {
        ajaxURL: endPointTable,
        ...tabulatorBaseConfig,
        ajaxParams: {
            filters: {
                ...filters,
            },
        },
        ajaxResponse: function (url, params, response) {
            updatePaginationInfo(
                emailNotificationsTable,
                response,
                "notification-email-table"
            );

            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });
}

function resetModal() {
    const form = document.getElementById("notification-email-form");
    form.reset();
    tomSelectRoles.clear();
    tomSelectUsers.clear();
    document.getElementById("notification_email_uid").value = "";

    resetFormErrors();
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

    const selectorFilter = document.getElementById("type-filter");
    const destinationRolesFilter = document.getElementById(
        "destination-roles-filter"
    );
    const destinationUsersFilter = document.getElementById(
        "destination-users-filter"
    );

    selectorFilter.addEventListener("change", function () {
        const selectedValueFilter = this.value;

        destinationRolesFilter.classList.add("no-visible");
        destinationUsersFilter.classList.add("no-visible");

        if (selectedValueFilter === "ROLES") {
            destinationRolesFilter.classList.remove("no-visible");
        } else if (selectedValueFilter === "USERS") {
            destinationUsersFilter.classList.remove("no-visible");
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

    const destinationRolesFilter = document.getElementById(
        "destination-roles-filter"
    );
    const destinationUsersFilter = document.getElementById(
        "destination-users-filter"
    );
    destinationRolesFilter.classList.add("no-visible");
    destinationUsersFilter.classList.add("no-visible");

    if (type === "ROLES") {
        destinationRolesFilter.classList.remove("no-visible");
    } else if (type === "USERS") {
        destinationUsersFilter.classList.remove("no-visible");
    }
}

function submitFormEmailNotificationModal() {
    const formData = new FormData(this);

    const roles = tomSelectRoles.items;
    formData.append("tags", JSON.stringify(roles));

    resetFormErrors("notification-email-form");

    const params = {
        url: "/notifications/email/save_email_notification",
        method: "POST",
        body: formData,
        loader: true,
    };

    apiFetch(params)
        .then(() => {
            emailNotificationsTable.replaceData(endPointTable);
            hideModal("notification-email-modal");

            setTimeout(() => {
                resetModal();
            }, 300);
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}
