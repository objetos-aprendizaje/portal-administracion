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

let centersTable;
let selectedCenters = [];
const endPointTable = "/administration/centers/get_centers";

document.addEventListener("DOMContentLoaded", function () {
    initHandlers();
    initializeCentersTable();
    controlReloadTable();
});

function initHandlers() {
    document
        .getElementById("new-center-btn")
        .addEventListener("click", () => {
            newCenter();
        });

    document
        .getElementById("btn-delete-center")
        .addEventListener("click", () => {
            if (selectedCenters.length) {
                showModalConfirmation(
                    "Eliminar centros",
                    "¿Está seguro que desea eliminar los centros?",
                    "delete_centers"
                ).then((resultado) => {
                    if (resultado) deleteCenters();
                });
            } else {
                showToast("Debe seleccionar al menos un centro", "error");
            }
        });

    document
        .getElementById("center-form")
        .addEventListener("submit", submitCenterForm);
}

/**
 * Configuración de la tabla de llamadas utilizando la biblioteca Tabulator.
 * Define las columnas, obtiene los datos del endpoint y maneja la paginación.
 */
function initializeCentersTable() {
    const columns = [
        {
            title: '<div class="checkbox-cell"><input type="checkbox" id="select-all-checkbox"/></div>',
            headerClick: function (e) {
                const selectAllCheckbox = e.target;
                if (selectAllCheckbox.type === "checkbox") {
                    // Asegúrate de que el clic fue en el checkbox
                    centersTable.getRows().forEach((row) => {
                        const cell = row.getCell("select");
                        const checkbox = cell
                            .getElement()
                            .querySelector('input[type="checkbox"]');
                        checkbox.checked = selectAllCheckbox.checked;
                        selectedCenters = updateArrayRecords(
                            checkbox,
                            row.getData(),
                            selectedCenters
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
                selectedCenters = updateArrayRecords(
                    checkbox,
                    cell.getRow().getData(),
                    selectedCenters
                );
            },
            width: 60,
            headerSort: false,
        },
        { title: "Nombre", field: "name"},
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

                const centerClicked = cell.getRow().getData();
                loadCenterModal(centerClicked.uid);
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
    ];

    centersTable = new Tabulator("#centers-table", {
        ajaxURL: endPointTable,
        ...tabulatorBaseConfig,
        ajaxResponse: function (url, params, response) {
            updatePaginationInfo(
                centersTable,
                response,
                "centers-table"
            );

            selectedCenters = [];

            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });

    controlsSearch(centersTable, endPointTable, "centers-table");
    controlsPagination(centersTable, "centers-table");
}

function newCenter() {
    resetModal();
    showModal("center-modal", "Añade un centro");
}

async function loadCenterModal(centerUid) {
    showLoader();

    const params = {
        url: `/administration/centers/get_center/${centerUid}`,
        method: "GET",
        loader: true,
    };

    apiFetch(params).then((response) => {
        showModal("center-modal", "Edita un centro");
        fillFormCenterModal(response);
    });
}

function fillFormCenterModal(center) {
    document.getElementById("center_uid").value = center.uid;
    document.getElementById("name").value = center.name;
}

/**
 * Este bloque maneja la presentación del formulario para una nueva llamada.
 * Recopila los datos y los envía a un endpoint específico.
 * Si la operación tiene éxito, actualiza la tabla y muestra un toast.
}
 */
function submitCenterForm() {
    const formData = new FormData(this);

    const params = {
        url: "/administration/centers/save_center",
        body: formData,
        method: "POST",
        loader: true,
        toast: true,
    };

    resetFormErrors("center-modal");

    apiFetch(params)
        .then(() => {
            centersTable.setData(endPointTable);
            hideModal("center-modal");
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

/**
 * Elimina los sistemas centros seleccionados.
 * Realiza una petición DELETE al servidor y actualiza la tabla si tiene éxito.
 */
async function deleteCenters() {
    const params = {
        url: "/administration/centers/delete_centers",
        body: { uids: selectedCenters.map((center) => center.uid) },
        method: "DELETE",
        loader: true,
        toast: true,
        stringify: true,
    };

    apiFetch(params).then(() => {
        centersTable.setData(endPointTable);
        hideModal("center-modal");
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
        centersTable.setData(endPointTable);
    });
}

/**
 * Reseteo del modal
 */
function resetModal() {
    const form = document.getElementById("center-form");
    form.reset();
    document.getElementById("center_uid").value = "";
    resetFormErrors();
}
