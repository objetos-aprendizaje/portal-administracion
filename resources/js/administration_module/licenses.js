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

let licensesTable;
let selectedLicenses = [];
const endPointTable = "/administration/licenses/get_licenses";

document.addEventListener("DOMContentLoaded", function () {
    initHandlers();
    initializeLicensesTable();
    controlReloadTable();
});

function initHandlers() {
    document
        .getElementById("new-license-btn")
        .addEventListener("click", () => {
            newLicense();
        });

    document
        .getElementById("btn-delete-license")
        .addEventListener("click", () => {
            if (selectedLicenses.length) {
                showModalConfirmation(
                    "Eliminar licencias",
                    "¿Está seguro que desea eliminar las licencias?",
                    "delete_licenses"
                ).then((resultado) => {
                    if (resultado) deleteLicenses();
                });
            } else {
                showToast("Debe seleccionar al menos una licencia", "error");
            }
        });

    document
        .getElementById("license-form")
        .addEventListener("submit", submitLicenseForm);
}


/**
 * Configuración de la tabla de llamadas utilizando la biblioteca Tabulator.
 * Define las columnas, obtiene los datos del endpoint y maneja la paginación.
 */
function initializeLicensesTable() {
    const columns = [
        {
            title: '<div class="checkbox-cell"><input type="checkbox" id="select-all-checkbox"/></div>',
            headerClick: function (e) {
                const selectAllCheckbox = e.target;
                if (selectAllCheckbox.type === "checkbox") {
                    // Asegúrate de que el clic fue en el checkbox
                    licensesTable.getRows().forEach((row) => {
                        const cell = row.getCell("select");
                        const checkbox = cell
                            .getElement()
                            .querySelector('input[type="checkbox"]');
                        checkbox.checked = selectAllCheckbox.checked;
                        selectedLicenses = updateArrayRecords(
                            checkbox,
                            row.getData(),
                            selectedLicenses
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
                selectedLicenses = updateArrayRecords(
                    checkbox,
                    cell.getRow().getData(),
                    selectedLicenses
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
                return `<button type="button" class='btn action-btn'>${heroicon(
                    "pencil-square",
                    "outline"
                )}</button>`;
            },
            cellClick: function (e, cell) {
                e.preventDefault();

                const licenseClicked = cell.getRow().getData();
                loadLicenseModal(licenseClicked.uid);
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
    ];

    licensesTable = new Tabulator("#licenses-table", {
        ajaxURL: endPointTable,
        ...tabulatorBaseConfig,
        ajaxResponse: function (url, params, response) {
            updatePaginationInfo(
                licensesTable,
                response,
                "licenses-table"
            );

            selectedLicenses = [];

            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });

    controlsSearch(licensesTable, endPointTable, "licenses-table");
    controlsPagination(licensesTable, "licenses-table");
}

function newLicense() {
    resetModal();
    showModal("license-modal", "Añade una licencia");
}

async function loadLicenseModal(licenseUid) {
    showLoader();

    const params = {
        url: `/administration/licenses/get_license/${licenseUid}`,
        method: "GET",
        loader: true,
    };

    apiFetch(params).then((response) => {
        showModal("license-modal", "Edita una licencia");
        fillFormLicenseModal(response);
    });
}

function fillFormLicenseModal(license) {
    document.getElementById("license_uid").value = license.uid;
    document.getElementById("name").value = license.name;
}

/**
 * Este bloque maneja la presentación del formulario para una nueva llamada.
 * Recopila los datos y los envía a un endpoint específico.
 * Si la operación tiene éxito, actualiza la tabla y muestra un toast.
}
 */
function submitLicenseForm() {
    const formData = new FormData(this);

    const params = {
        url: "/administration/licenses/save_license",
        body: formData,
        method: "POST",
        loader: true,
        toast: true,
    };

    resetFormErrors("license-modal");

    apiFetch(params)
        .then(() => {
            licensesTable.replaceData(endPointTable);
            hideModal("license-modal");
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

/**
 * Elimina los sistemas centros seleccionados.
 * Realiza una petición DELETE al servidor y actualiza la tabla si tiene éxito.
 */
async function deleteLicenses() {
    const params = {
        url: "/administration/licenses/delete_licenses",
        body: { uids: selectedLicenses.map((licence) => licence.uid) },
        method: "DELETE",
        loader: true,
        toast: true,
        stringify: true,
    };

    apiFetch(params).then(() => {
        licensesTable.replaceData(endPointTable);
        hideModal("licence-modal");
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
        licensesTable.replaceData(endPointTable);
    });
}

/**
 * Reseteo del modal
 */
function resetModal() {
    const form = document.getElementById("license-form");
    form.reset();
    document.getElementById("license_uid").value = "";
    resetFormErrors();
}
