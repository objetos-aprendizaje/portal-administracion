import { heroicon } from "../heroicons.js";
import { hideModal, showModal, showModalConfirmation } from "../modal_handler";
import {
    getCsrfToken,
    showFormErrors,
    resetFormErrors,
    updateInputImage,
    getMultipleTomSelectInstance,
    instanceFlatpickr,
    apiFetch,
} from "../app.js";
import {
    controlsPagination,
    updateArrayRecords,
    tabulatorBaseConfig,
    updatePaginationInfo,
    controlsSearch,
    formatDateTime,
} from "../tabulator_handler";
import { TabulatorFull as Tabulator } from "tabulator-tables";
import { showToast } from "../toast.js";

const endPointTable = "/users/list_users/get_users";
let usersTable;
let tomSelectRoles;

let selectedUsers = [];

let dateUsersFilterFlatpickr;
let tomSelectRolesFilter;

document.addEventListener("DOMContentLoaded", async function () {
    initHandlers();

    dateUsersFilterFlatpickr = instanceFlatpickr("date_users");
    initializeUsersTable();

    initializeTomSelect();
    controlsSearch(usersTable, endPointTable, "users-table");
    controlsPagination(usersTable, "users-table");
    updateInputImage();
});

function initHandlers() {
    document
        .getElementById("add-user-btn")
        .addEventListener("click", function () {
            resetModal();
            newUser();
        });

    document
        .getElementById("user-form")
        .addEventListener("submit", submitFormUserModal);

    document
        .getElementById("filter-users-btn")
        .addEventListener("click", function () {
            showModal("filter-users-modal");
        });

    document.getElementById("delete-user-btn").addEventListener("click", () => {
        if (selectedUsers.length) {
            showModalConfirmation(
                "Eliminar usuarios",
                "¿Está seguro que desea eliminar los usuarios seleccionados?",
                "delete_users"
            ).then((resultado) => {
                if (resultado) {
                    deleteUsers();
                }
            });
        } else {
            showToast("Debe seleccionar al menos una convocatoria", "error");
        }
    });
}

/**
 * Inicializa el componente TomSelect para el campo de roles.
 */
function initializeTomSelect() {
    tomSelectRoles = getMultipleTomSelectInstance("#roles");
    tomSelectRolesFilter = getMultipleTomSelectInstance("#roles_filter");

    fetch("/users/list_users/get_user_roles", {
        method: "GET",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": getCsrfToken(),
        },
    }).then(async (response) => {
        const data = await response.json();
        if (response.status === 200) {
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
        }
    });
}

/**
 * Elimina usuarios seleccionados.
 * Realiza una petición DELETE al servidor y actualiza la tabla si tiene éxito.
 */
async function deleteUsers() {
    const params = {
        method: "DELETE",
        body: { usersUids: selectedUsers.map((user) => user.uid) },
        toast: true,
        loader: true,
    };

    apiFetch(params).then(() => {
        usersTable.replaceData(endPointTable);
    });
}

function newUser() {
    resetModal();
    showModal("user-modal", "Añadir usuario");
}

async function initializeUsersTable() {
    return new Promise((resolve, reject) => {
        const columns = [
            {
                title: '<div class="checkbox-cell"><input type="checkbox" id="select-all-checkbox"/></div>',
                headerClick: function (e) {
                    const selectAllCheckbox = e.target;
                    if (selectAllCheckbox.type === "checkbox") {
                        // Asegúrate de que el clic fue en el checkbox
                        usersTable.getRows().forEach((row) => {
                            const cell = row.getCell("select");
                            const checkbox = cell
                                .getElement()
                                .querySelector('input[type="checkbox"]');
                            checkbox.checked = selectAllCheckbox.checked;
                            selectedUsers = updateArrayRecords(
                                checkbox,
                                row.getData(),
                                selectedUsers
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

                    selectedUsers = updateArrayRecords(
                        checkbox,
                        rowData,
                        selectedUsers
                    );
                },
                headerSort: false,
                width: 60,
            },
            {
                field: "photo_path",
                formatter: function (cell, formatterParams, onRendered) {
                    const imagePath = cell.getValue()
                        ? cell.getValue()
                        : "default_images/no-user.svg";
                    return `
                <div class="flex justify-center">
                    <img src="/${imagePath}" alt="Imagen" class="w-8 rounded-full">
                </div>`;
                },
                headerSort: false,
                cssClass: "",
                width: 60,
            },
            { title: "Nombre", field: "first_name" },
            { title: "Apellidos", field: "last_name" },
            { title: "Email", field: "email" },
            { title: "NIF", field: "nif" },
            {
                title: "Roles",
                formatter: function (cell, formatterParams, onRendered) {
                    const roles = cell.getRow().getData().roles;

                    if (!roles || !roles.length) return;

                    const rolesString = roles
                        .map((rol) => {
                            return `<span">${rol.name}</span>`;
                        })
                        .join(", ");

                    return rolesString;
                },
                headerSort: false,
            },
            {
                title: "Fecha creación",
                field: "created_at",
                formatter: function (cell, formatterParams, onRendered) {
                    const dateCreation = cell.getRow().getData().created_at;

                    if (dateCreation) return formatDateTime(dateCreation);
                    else return "";
                },
            },
            {
                title: "",
                field: "actions",
                formatter: function (cell, formatterParams, onRendered) {
                    return `<button type="button" class='btn action-btn'>${heroicon(
                        "pencil-square",
                        "outline"
                    )}</button>`;
                },
                cellClick: function (e, cell) {
                    e.preventDefault();
                    const userClicked = cell.getRow().getData();
                    loadUserModal(userClicked.uid);
                },
                cssClass: "text-center",
                headerSort: false,
                width: 30,
                resizable: false,
            },
        ];

        usersTable = new Tabulator("#users-table", {
            ajaxURL: endPointTable,
            ...tabulatorBaseConfig,
            ajaxResponse: function (url, params, response) {
                updatePaginationInfo(usersTable, response, "users-table");

                selectedUsers = [];

                return {
                    last_page: response.last_page,
                    data: response.data,
                };
            },
            columns: columns,
        });

        usersTable.on("dataLoaded", function () {
            resolve();
        });

        usersTable.on("dataLoadError", function (error) {
            reject(error);
        });
    });
}

async function loadUserModal(uid) {
    const params = {
        url: `/users/list_users/get_user/${uid}`,
        method: "GET",
    };

    apiFetch(params).then((data) => {
        fillFormUserModal(data);
        showModal("user-modal", "Editar usuario");
    });
}

function fillFormUserModal(user) {
    resetModal();
    document.getElementById("first_name").value = user.first_name;
    document.getElementById("last_name").value = user.last_name;
    document.getElementById("nif").value = user.nif;
    document.getElementById("email").value = user.email;
    document.getElementById("curriculum").value = user.curriculum;
    document.getElementById("user_uid").value = user.uid;

    if (user.roles.length) {
        user.roles.forEach((rol) => {
            tomSelectRoles.addItem(rol.uid);
        });
    } else {
        tomSelectRoles.clear();
    }

    const imgElement = document.getElementById("photo_path_preview");

    if (user.photo_path) {
        imgElement.src = "/" + user.photo_path;
    } else {
        imgElement.src = defaultImagePreview;
    }
}

/**
 * Envía el formulario del modal de usuario.
 * Realiza una petición POST al servidor con los datos del formulario.
 */
function submitFormUserModal() {
    const formData = new FormData(this);
    const roles = tomSelectRoles.items;
    formData.append("roles", JSON.stringify(roles));

    const params = {
        method: "POST",
        body: formData,
        toast: true,
        loader: true,
        url: "/users/list_users/save_user"
    };

    resetFormErrors("user-form");

    apiFetch(params)
        .then(() => {
            usersTable.replaceData(endPointTable);
            const modal = document.getElementById("user-modal");
            hideModal("user-modal");
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

function resetModal() {
    const form = document.getElementById("user-form");
    form.reset();
    resetFormErrors();

    const imgElement = document.getElementById("photo_path_preview");
    imgElement.src = defaultImagePreview;

    tomSelectRoles.clear();

    document.getElementById("user_uid").value = "";
    document.getElementById("image-name").innerText =
        "Ningún archivo seleccionado";
}
