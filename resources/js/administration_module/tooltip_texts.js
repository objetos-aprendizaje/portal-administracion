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

let tooltipTextsTable;
let selectedTooltipTexts = [];
const endPointTable = "/administration/tooltip_texts/get_tooltip_texts";

document.addEventListener("DOMContentLoaded", function () {
    initHandlers();
    initializeTooltipTextsTable();
    controlReloadTable();
});

function initHandlers() {
    document
        .getElementById("new-tooltip-texts-btn")
        .addEventListener("click", () => {
            newTooltipText();
        });

    document
        .getElementById("btn-delete-tooltip-texts")
        .addEventListener("click", () => {
            if (selectedTooltipTexts.length) {
                showModalConfirmation(
                    "Eliminar licencias",
                    "¿Está seguro que desea eliminar los textos?",
                    "delete_tooltip_texts"
                ).then((resultado) => {
                    if (resultado) deleteTooltipTexts();
                });
            } else {
                showToast("Debe seleccionar al menos un texto", "error");
            }
        });

    document
        .getElementById("tooltip-texts-form")
        .addEventListener("submit", submitTooltipTextsForm);
}

/**
 * Configuración de la tabla de llamadas utilizando la biblioteca Tabulator.
 * Define las columnas, obtiene los datos del endpoint y maneja la paginación.
 */
function initializeTooltipTextsTable() {
    const columns = [
        {
            title: '<div class="checkbox-cell"><input type="checkbox" id="select-all-checkbox"/></div>',
            headerClick: function (e) {
                const selectAllCheckbox = e.target;
                if (selectAllCheckbox.type === "checkbox") {
                    // Asegúrate de que el clic fue en el checkbox
                    tooltipTextsTable.getRows().forEach((row) => {
                        const cell = row.getCell("select");
                        const checkbox = cell
                            .getElement()
                            .querySelector('input[type="checkbox"]');
                        checkbox.checked = selectAllCheckbox.checked;
                        selectedTooltipTexts = updateArrayRecords(
                            checkbox,
                            row.getData(),
                            selectedTooltipTexts
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
                selectedTooltipTexts = updateArrayRecords(
                    checkbox,
                    cell.getRow().getData(),
                    selectedTooltipTexts
                );
            },
            width: 60,
            headerSort: false,
        },
        { title: "ID del Formulario", field: "form_id"},
        { title: "ID del Campo", field: "input_id"},
        { title: "Descripción", field: "description"},
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

                const tooltipTextClicked = cell.getRow().getData();
                loadTooltipTextsModal(tooltipTextClicked.uid);
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
    ];

    tooltipTextsTable = new Tabulator("#tooltip-texts-table", {
        ajaxURL: endPointTable,
        ...tabulatorBaseConfig,
        ajaxResponse: function (url, params, response) {
            updatePaginationInfo(
                tooltipTextsTable,
                response,
                "tooltip-texts-table"
            );

            selectedTooltipTexts = [];

            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });

    controlsSearch(tooltipTextsTable, endPointTable, "tooltip-texts-table");
    controlsPagination(tooltipTextsTable, "tooltip-texts-table");
}

function newTooltipText() {
    resetModal();
    showModal("tooltip-texts-modal", "Añade un tooltip");
}

async function loadTooltipTextsModal(tooltipTextUid) {
    showLoader();

    const params = {
        url: `/administration/tooltip_texts/get_tooltip_text/${tooltipTextUid}`,
        method: "GET",
        loader: true,
    };

    apiFetch(params).then((response) => {
        showModal("tooltip-texts-modal", "Edita un texto");
        fillFormTooltipTextModal(response);
    });
}

function fillFormTooltipTextModal(tooltiptext) {
    document.getElementById("tooltip_text_uid").value = tooltiptext.uid;
    document.getElementById("input_id").value = tooltiptext.input_id;
    document.getElementById("form_id").value = tooltiptext.form_id;
    document.getElementById("description").value = tooltiptext.description;
}

/**
 * Este bloque maneja la presentación del formulario para una nueva llamada.
 * Recopila los datos y los envía a un endpoint específico.
 * Si la operación tiene éxito, actualiza la tabla y muestra un toast.
}
 */
function submitTooltipTextsForm() {
    const formData = new FormData(this);

    const params = {
        url: "/administration/tooltip_texts/save_tooltip_text",
        body: formData,
        method: "POST",
        loader: true,
        toast: true,
    };

    resetFormErrors("tooltip-texts-modal");

    apiFetch(params)
        .then(() => {
            tooltipTextsTable.replaceData(endPointTable);
            hideModal("tooltip-texts-modal");
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

/**
 * Elimina los sistemas centros seleccionados.
 * Realiza una petición DELETE al servidor y actualiza la tabla si tiene éxito.
 */
async function deleteTooltipTexts() {
    const params = {
        url: "/administration/tooltip_texts/delete_tooltip_texts",
        body: { uids: selectedTooltipTexts.map((tooltip_text) => tooltip_text.uid) },
        method: "DELETE",
        loader: true,
        toast: true,
        stringify: true,
    };

    apiFetch(params).then(() => {
        tooltipTextsTable.replaceData(endPointTable);
        hideModal("tooltip-texts-modal");
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
        tooltipTextsTable.replaceData(endPointTable);
    });
}

/**
 * Reseteo del modal
 */
function resetModal() {
    const form = document.getElementById("tooltip-texts-form");
    form.reset();
    document.getElementById("tooltip_text_uid").value = "";
    resetFormErrors();
}
