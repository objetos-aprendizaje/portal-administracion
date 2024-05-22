import { TabulatorFull as Tabulator } from "tabulator-tables";
import {
    updateArrayRecords,
    tabulatorBaseConfig,
    updatePaginationInfo,
} from "../tabulator_handler";
import { heroicon } from "../heroicons";
import {
    showFormErrors,
    resetFormErrors,
    resetFormFields,
    apiFetch,
} from "../app.js";
import { showModal, hideModal } from "../modal_handler";

let apiKeyTable;
let selectedApiKeys = [];

const endPointTable = "/administration/api_keys/get_api_keys";

document.addEventListener("DOMContentLoaded", async function () {
    initializeApiKeysTable();
    initHandlers();
});

function initHandlers() {
    document.getElementById("new-api-key-btn").addEventListener("click", () => {
        newApiKey();
    });

    document
        .getElementById("api-key-form")
        .addEventListener("submit", submitNewApiKey);
}

function newApiKey() {
    resetFormFields("api-key-form");
    showModal("api-key-modal", "Añade una clave API");
}

function initializeApiKeysTable() {
    const columns = [
        {
            title: '<div class="checkbox-cell"><input type="checkbox" id="select-all-checkbox"/></div>',
            headerClick: function (e) {
                const selectAllCheckbox = e.target;
                if (selectAllCheckbox.type === "checkbox") {
                    // Asegúrate de que el clic fue en el checkbox
                    apiKeyTable.getRows().forEach((row) => {
                        const cell = row.getCell("select");
                        const checkbox = cell
                            .getElement()
                            .querySelector('input[type="checkbox"]');
                        checkbox.checked = selectAllCheckbox.checked;
                        selectedApiKeys = updateArrayRecords(
                            checkbox,
                            row.getData(),
                            selectedApiKeys
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

                selectedApiKeys = updateArrayRecords(
                    checkbox,
                    rowData,
                    selectedApiKeys
                );
            },
            headerSort: false,
            width: 60,
        },
        { title: "Nombre", field: "name", widthGrow: "2" },
        { title: "Clave API", field: "api_key", widthGrow: "2" },
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
                const apiKeyClicked = cell.getRow().getData();
                loadApiKeyModal(apiKeyClicked.uid);
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
    ];

    apiKeyTable = new Tabulator("#api-keys-table", {
        ajaxURL: endPointTable,
        ...tabulatorBaseConfig,
        ajaxResponse: function (url, params, response) {
            updatePaginationInfo(apiKeyTable, response, "api-keys-table");

            selectedApiKeys = [];

            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });

    controlsSearch(apiKeyTable, endPointTable, "api-keys-table");
    controlsPagination(apiKeyTable, "api-keys-table");
}

async function loadApiKeyModal(apiKeyUid) {
    const params = {
        url: `/administration/api_keys/get_api_key/${apiKeyUid}`,
        method: "GET",
        stringify: true,
        loader: true,
    };

    apiFetch(params).then(async (response) => {
        showModal("api-key-modal", "Edita una clave API");
        fillApiKeyModal(response);
    });
}

function fillApiKeyModal(apiKey) {
    resetFormErrors("api-key-form");
    resetFormFields("api-key-form");
    document.getElementById("api_key_uid").value = apiKey.uid;
    document.getElementById("name").value = apiKey.name;
    document.getElementById("api_key").value = apiKey.api_key;
}

/**
 * Este bloque maneja la presentación del formulario para una nueva redirección.
 * Recopila los datos y los envía a un endpoint específico.
 * Si la operación tiene éxito, actualiza la tabla y muestra un toast.
 */
function submitNewApiKey() {
    const formData = new FormData(this);

    resetFormErrors("api-key-form");

    const params = {
        url: "/administration/api_keys/save_api_key",
        method: "POST",
        body: formData,
        loader: true,
    };

    apiFetch(params, formData)
        .then(() => {
            hideModal("api-key-modal");
            apiKeyTable.setData(endPointTable);
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}
