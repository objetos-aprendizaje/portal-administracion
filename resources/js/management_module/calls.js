import {
    showModal,
    hideModal,
    showModalConfirmation,
} from "../modal_handler.js";
import {
    controlsPagination,
    updateArrayRecords,
    formatDate,
    updatePaginationInfo,
    tabulatorBaseConfig,
    controlsSearch,
} from "../tabulator_handler.js";
import {
    resetFormErrors,
    showFormErrors,
    updateInputFile,
    getMultipleTomSelectInstance,
    apiFetch,
} from "../app.js";
import { heroicon } from "../heroicons.js";
import { TabulatorFull as Tabulator } from "tabulator-tables";
import { showToast } from "../toast.js";

let callsTable;
let selectedCalls = [];
let tomSelectCallsEducationalProgramTypes;
const endPointTable = "/management/calls/get_calls";

document.addEventListener("DOMContentLoaded", () => {
    initHandlers();

    initializeCallsTable();
    controlsPagination(callsTable, "calls-table");
    controlsSearch(callsTable, endPointTable, "calls-table");
    initTomSelect();
    updateInputFile();
});

function initHandlers() {
    document
        .getElementById("btn-reload-table")
        .addEventListener("click", function () {
            callsTable.replaceData(endPointTable);
        });

    document.getElementById("new-call-btn").addEventListener("click", newCall);

    document.getElementById("btn-edit-call").addEventListener("click", () => {
        if (selectedCalls.length) {
            showModalConfirmation(
                "Eliminar convocatoria",
                "¿Está seguro que desea eliminar las convocatorias seleccionadas?",
                "delete_calls"
            ).then((resultado) => {
                if (resultado) deleteCalls();
            });
        } else {
            showToast("Debe seleccionar al menos una convocatoria", "error");
        }
    });

    document
        .getElementById("call-form")
        .addEventListener("submit", submitFormCall);
}

function initTomSelect() {
    tomSelectCallsEducationalProgramTypes = getMultipleTomSelectInstance(
        "#program_types"
    );
}

function newCall() {
    resetModal();
    showModal("call-modal", "Crear convocatoria");
}

/**
 * Configuración de la tabla de llamadas utilizando la biblioteca Tabulator.
 * Define las columnas, obtiene los datos del endpoint y maneja la paginación.
 */
function initializeCallsTable() {
    const columns = [
        {
            title: '<div class="checkbox-cell"><input type="checkbox" id="select-all-checkbox"/></div>',
            headerClick: function (e) {
                const selectAllCheckbox = e.target;
                if (selectAllCheckbox.type === "checkbox") {
                    // Asegúrate de que el clic fue en el checkbox
                    callsTable.getRows().forEach((row) => {
                        const cell = row.getCell("select");
                        const checkbox = cell
                            .getElement()
                            .querySelector('input[type="checkbox"]');
                        checkbox.checked = selectAllCheckbox.checked;
                        selectedCalls = updateArrayRecords(
                            checkbox,
                            row.getData(),
                            selectedCalls
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
                selectedCalls = updateArrayRecords(
                    checkbox,
                    cell.getRow().getData(),
                    selectedCalls
                );
            },
            width: 60,
            headerSort: false,
        },
        { title: "Nombre", field: "name", widthGrow: 6 },
        {
            title: "Fecha Inicio",
            field: "start_date",
            widthGrow: 2,
            formatter: function (cell, formatterParams, onRendered) {
                const isoDate = cell.getValue();
                if (!isoDate) return "";
                return formatDate(isoDate);
            },
        },
        {
            title: "Fecha Fin",
            field: "end_date",
            widthGrow: 2,
            formatter: function (cell, formatterParams, onRendered) {
                const isoDate = cell.getValue();
                if (!isoDate) return "";
                return formatDate(isoDate);
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

                const callClicked = cell.getRow().getData();
                loadCallModal(callClicked.uid);
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
    ];

    callsTable = new Tabulator("#calls-table", {
        ajaxURL: endPointTable,
        ...tabulatorBaseConfig,

        ajaxResponse: function (url, params, response) {
            updatePaginationInfo(callsTable, response, "calls-table");

            selectedCalls = [];

            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });
}

/**
 * Este bloque maneja la presentación del formulario para una nueva llamada.
 * Recopila los datos y los envía a un endpoint específico.
 * Si la operación tiene éxito, actualiza la tabla y muestra un toast.
}
 */
function submitFormCall() {
    const formData = new FormData(this);

    resetFormErrors("call-form");

    const params = {
        url: "/management/calls/save_call",
        method: "POST",
        body: formData,
        toast: true,
        loader: true,
        //stringify: true
    };

    apiFetch(params)
        .then(() => {
            callsTable.replaceData(endPointTable);
            hideModal("call-modal");
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

async function loadCallModal(callUid) {

    const params = {
        url: `/management/calls/get_call/${callUid}`,
        method: "GET",
    }

    apiFetch(params).then((data) => {
        resetModal();
        fillFormCallModal(data);
        showModal("call-modal", "Editar convocatoria");
    })
}

function fillFormCallModal(call) {
    document.getElementById("name").value = call.name;
    document.getElementById("description").value = call.description;
    document.getElementById("start_date").value = call.start_date;
    document.getElementById("end_date").value = call.end_date;
    document.getElementById("call_uid").value = call.uid;

    if (call.attachment_path) {
        const downloadLinkHref = document.getElementById("attachment-download");
        downloadLinkHref.classList.remove("hidden");
        downloadLinkHref.href = "/" + call.attachment_path;
        const attachmentName = call.attachment_path.split("/").pop();
        downloadLinkHref.innerText = attachmentName;
    }

    call.educational_program_types.forEach((educational_program_type) => {
        tomSelectCallsEducationalProgramTypes.addOption({
            value: educational_program_type.uid,
            text: educational_program_type.name,
        });

        tomSelectCallsEducationalProgramTypes.addItem(
            educational_program_type.uid
        );
    });
}

/**
 * Reseteo del modal
 */
function resetModal() {
    const form = document.getElementById("call-form");
    form.reset();
    document.getElementById("call_uid").value = "";
    resetFormErrors();
    tomSelectCallsEducationalProgramTypes.clear();
    document.getElementById("attachment-download").classList.add("hidden");
    document.getElementById("file-name").innerText =
        "Ningún archivo seleccionado";
}

/**
 * Elimina las convocatorias seleccionadas.
 * Realiza una petición DELETE al servidor y actualiza la tabla si tiene éxito.
 */
async function deleteCalls() {
    const params = {
        url: "/management/calls/delete_calls",
        method: "DELETE",
        body: { uids: selectedCalls.map((call) => call.uid)},
        toast: true,
        loader: true,
        stringify: true
    }

    apiFetch(params).then((data) => {
        callsTable.replaceData(endPointTable);
    });
}
