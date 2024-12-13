import {
    controlsPagination,
    updateArrayRecords,
    updatePaginationInfo,
    tabulatorBaseConfig,
    controlsSearch,
} from "../tabulator_handler.js";
import { TabulatorFull as Tabulator } from "tabulator-tables";
import { heroicon } from "../heroicons.js";
import {
    showModal,
    hideModal,
    showModalConfirmation,
} from "../modal_handler.js";
import {
    resetFormErrors,
    showFormErrors,
    showLoader,
    apiFetch,
} from "../app.js";
import { showToast } from "../toast.js";

let lmsSystemsTable;
let selectedLmsSystems = [];
const endPointTable = "/administration/lms_systems/get_lms_systems";

document.addEventListener("DOMContentLoaded", function () {
    initHandlers();
    initializeLmsSystemsTable();

    controlReloadTable();
});

function initHandlers() {
    document
        .getElementById("new-lms-system-btn")
        .addEventListener("click", () => {
            newLmsSystem();
        });

    document
        .getElementById("btn-delete-lms-system")
        .addEventListener("click", () => {
            if (selectedLmsSystems.length) {
                showModalConfirmation(
                    "Eliminar sistemas LMS",
                    "¿Está seguro que desea eliminar los sistemas LMS seleccionados?",
                    "delete_lms"
                ).then((resultado) => {
                    if (resultado) deleteLmsSystems();
                });
            } else {
                showToast("Debe seleccionar al menos un LMS", "error");
            }
        });

    document
        .getElementById("lms-system-form")
        .addEventListener("submit", submitLmsSystemForm);
}

/**
 * Configuración de la tabla de llamadas utilizando la biblioteca Tabulator.
 * Define las columnas, obtiene los datos del endpoint y maneja la paginación.
 */
function initializeLmsSystemsTable() {
    const columns = [
        {
            title: '<div class="checkbox-cell"><input type="checkbox" id="select-all-checkbox"/></div>',
            headerClick: function (e) {
                const selectAllCheckbox = e.target;
                if (selectAllCheckbox.type === "checkbox") {
                    // Asegúrate de que el clic fue en el checkbox
                    lmsSystemsTable.getRows().forEach((row) => {
                        const cell = row.getCell("select");
                        const checkbox = cell
                            .getElement()
                            .querySelector('input[type="checkbox"]');
                        checkbox.checked = selectAllCheckbox.checked;
                        selectedLmsSystems = updateArrayRecords(
                            checkbox,
                            row.getData(),
                            selectedLmsSystems
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
                selectedLmsSystems = updateArrayRecords(
                    checkbox,
                    cell.getRow().getData(),
                    selectedLmsSystems
                );
            },
            width: 60,
            headerSort: false,
        },
        { title: "Nombre", field: "name", widthGrow: 5 },
        { title: "Identificador", field: "identifier", widthGrow: 5 },
        {
            title: "",
            field: "actions",
            formatter: function (cell, formatterParams, onRendered) {
                return `<button type="button" class='btn action-btn' title='Editar'>${heroicon(
                    "pencil-square",
                    "outline"
                )}</button>`;
            },
            cellClick: function (e, cell) {
                e.preventDefault();

                const lmsSystemClicked = cell.getRow().getData();
                loadLmsSystemModal(lmsSystemClicked.uid);
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
    ];

    lmsSystemsTable = new Tabulator("#lms-systems-table", {
        ajaxURL: endPointTable,
        ...tabulatorBaseConfig,
        ajaxResponse: function (url, params, response) {
            updatePaginationInfo(
                lmsSystemsTable,
                response,
                "lms-systems-table"
            );

            selectedLmsSystems = [];

            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });

    controlsSearch(lmsSystemsTable, endPointTable, "lms-systems-table");
    controlsPagination(lmsSystemsTable, "lms-systems-table");
}

function newLmsSystem() {
    resetModal();
    showModal("lms-system-modal", "Añade una clave API");
}

async function loadLmsSystemModal(lmsSystemUid) {
    showLoader();

    const params = {
        url: `/administration/lms_systems/get_lms_system/${lmsSystemUid}`,
        method: "GET",
        loader: true,
    };

    apiFetch(params).then((response) => {
        showModal("lms-system-modal", "Edita un sistema LMS");
        fillFormLmsSystemModal(response);
    });
}

function fillFormLmsSystemModal(lmsSystem) {
    document.getElementById("lms_system_uid").value = lmsSystem.uid;
    document.getElementById("name").value = lmsSystem.name;
    document.getElementById("identifier").value = lmsSystem.identifier;
}

/**
 * Este bloque maneja la presentación del formulario para una nueva llamada.
 * Recopila los datos y los envía a un endpoint específico.
 * Si la operación tiene éxito, actualiza la tabla y muestra un toast.
}
 */
function submitLmsSystemForm() {
    const formData = new FormData(this);

    const params = {
        url: "/administration/lms_systems/save_lms_system",
        body: formData,
        method: "POST",
        loader: true,
        toast: true,
    };

    resetFormErrors();

    apiFetch(params)
        .then(() => {
            lmsSystemsTable.setData(endPointTable);
            hideModal("lms-system-modal");
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

/**
 * Elimina los sistemas LMS seleccionados.
 * Realiza una petición DELETE al servidor y actualiza la tabla si tiene éxito.
 */
async function deleteLmsSystems() {
    const params = {
        url: "/administration/lms_systems/delete_lms_systems",
        body: { uids: selectedLmsSystems.map((lmsSystem) => lmsSystem.uid) },
        method: "DELETE",
        loader: true,
        toast: true,
        stringify: true,
    };

    apiFetch(params).then(() => {
        lmsSystemsTable.setData(endPointTable);
        hideModal("lms-system-modal");
    });
}

/**
 * Este bloque escucha el evento de clic en el botón de recargar tabla
 * y actualiza los datos de la tabla.
 *
 */
function controlReloadTable() {
    const reloadTableBtn = document.getElementById("btn-reload-table");
    reloadTableBtn.addEventListener("click", function () {
        lmsSystemsTable.setData(endPointTable);
    });
}

/**
 * Reseteo del modal
 */
function resetModal() {
    const form = document.getElementById("lms-system-form");
    form.reset();
    document.getElementById("lms_system_uid").value = "";
    resetFormErrors();
}
