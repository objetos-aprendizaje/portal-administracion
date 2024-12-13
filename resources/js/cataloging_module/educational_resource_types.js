import { heroicon } from "../heroicons.js";
import { hideModal, showModal, showModalConfirmation } from "../modal_handler";
import { resetFormErrors, apiFetch,showFormErrors } from "../app.js";
import {
    controlsPagination,
    updateArrayRecords,
    tabulatorBaseConfig,
    updatePaginationInfo,
    controlsSearch,
} from "../tabulator_handler";
import { TabulatorFull as Tabulator } from "tabulator-tables";
import { showToast } from "../toast.js";

const endPointTable =
    "/cataloging/educational_resources_types/get_list_educational_resource_types";
let educationalResourceTypesTable;

let selectedEducationalResourceTypes = [];

document.addEventListener("DOMContentLoaded", function () {
    initHandlers();
    initializeEducationalResourceTypesTable();

});

function initHandlers() {
    document
        .getElementById("add-educational-resource-type-btn")
        .addEventListener("click", function () {
            newEducationalResourceType();
        });

    document
        .getElementById("educational-resource-type-form")
        .addEventListener("submit", submitFormEducationalResourceTypeModal);

    document
        .getElementById("delete-educational-resource-type-btn")
        .addEventListener("click", () => {
            if (selectedEducationalResourceTypes.length) {
                showModalConfirmation(
                    "Eliminar tipos de recurso educativo",
                    "¿Está seguro que desea eliminar los tipos de recurso educativo seleccionados?",
                    "delete_educational_resource_types"
                ).then((resultado) => {
                    if (resultado) {
                        deleteEducationalResourceTypes();
                    }
                });
            } else {
                showToast(
                    "Debe seleccionar al menos un tipo de recurso educativo",
                    "error"
                );
            }
        });
}

function newEducationalResourceType() {
    resetFormErrors("educational-resource-type-form");
    resetModal();
    showModal("educational-resource-types-modal", "Añade un nuevo tipo de recurso educativo");
}

function initializeEducationalResourceTypesTable() {
    const columns = [
        {
            title: '<div class="checkbox-cell"><input type="checkbox" id="select-all-checkbox"/></div>',
            headerClick: function (e) {
                const selectAllCheckbox = e.target;
                if (selectAllCheckbox.type === "checkbox") {
                    // Asegúrate de que el clic fue en el checkbox
                    educationalResourceTypesTable.getRows().forEach((row) => {
                        const cell = row.getCell("select");
                        const checkbox = cell
                            .getElement()
                            .querySelector('input[type="checkbox"]');
                        checkbox.checked = selectAllCheckbox.checked;
                        updateArrayRecords(
                            checkbox,
                            row.getData(),
                            selectedEducationalResourceTypes
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

                selectedEducationalResourceTypes = updateArrayRecords(
                    checkbox,
                    rowData,
                    selectedEducationalResourceTypes
                );
            },
            headerSort: false,
            width: 60,
        },
        { title: "Nombre", field: "name" },
        { title: "Descripción", field: "description" },
        {
            title: "",
            field: "actions",
            formatter: function (cell, formatterParams, onRendered) {
                return `
                    <button type="button" class='btn action-btn' title='Editar'>${heroicon(
                        "pencil-square",
                        "outline"
                    )}</button>
                `;
            },
            cellClick: function (e, cell) {
                e.preventDefault();
                const educationalResourceTypeClicked = cell.getRow().getData();
                loadEducationalResourceTypeModal(
                    educationalResourceTypeClicked.uid
                );
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
    ];

    educationalResourceTypesTable = new Tabulator(
        "#educational-resource-types-table",
        {
            ajaxURL: endPointTable,
            ...tabulatorBaseConfig,
            ajaxResponse: function (url, params, response) {
                updatePaginationInfo(
                    educationalResourceTypesTable,
                    response,
                    "educational-resource-types-table"
                );

                selectedEducationalResourceTypes = [];

                return {
                    last_page: response.last_page,
                    data: response.data,
                };
            },
            columns: columns,
        }
    );

    controlsSearch(
        educationalResourceTypesTable,
        endPointTable,
        "educational-resource-types-table"
    );

    controlsPagination(
        educationalResourceTypesTable,
        "educational-resource-types-table"
    );
}

async function loadEducationalResourceTypeModal(uid) {
    const params = {
        url: `/cataloging/educational_resources_types/get_educational_resource_type/${uid}`,
        method: "GET",
        loader: true,
    };

    apiFetch(params).then((data) => {
        fillFormEducationalResourceTypeModal(data);
        showModal(
            "educational-resource-types-modal",
            "Editar tipo de recurso educativo"
        );
    });
}

function fillFormEducationalResourceTypeModal(educational_resource_type) {
    document.getElementById("name").value = educational_resource_type.name;
    document.getElementById("description").value =
        educational_resource_type.description;
    document.getElementById("educational_resource_type_uid").value =
        educational_resource_type.uid;
}

/**
 * Envía el formulario del modal de tipo de recurso educativo.
 * Realiza una petición POST al servidor con los datos del formulario.
 */
function submitFormEducationalResourceTypeModal() {
    const formData = new FormData(this);
    const params = {
        url: "/cataloging/educational_resources_types/save_educational_resource_type",
        method: "POST",
        body: formData,
        toast: true,
        loader: true,
    };

    apiFetch(params).then(() => {
        resetModal();
        educationalResourceTypesTable.replaceData(endPointTable);
        hideModal("educational-resource-types-modal");
    }).catch((data) => {
        showFormErrors(data.errors);
    });
}

function resetModal() {
    const form = document.getElementById("educational-resource-type-form");
    form.reset();
    // Reseteo del campo uid ya que al ser hidden, no le afecta form.reset()
    // Reseteamos solo este campo y no todos los hidden porque si no nos cargamos el campo del token csrf
    document.getElementById("educational_resource_type_uid").value = "";

    resetFormErrors("educational-resource-type-form");
}

/**
 * Elimina tipos de recursos educativo seleccionados.
 * Realiza una petición DELETE al servidor y actualiza la tabla si tiene éxito.
 */
async function deleteEducationalResourceTypes() {
    const params = {
        url: "/cataloging/educational_resources_types/delete_educational_resource_types",
        method: "DELETE",
        body: {
            uids: selectedEducationalResourceTypes.map((type) => type.uid),
        },
        loader: true,
        toast: true,
        stringify: true,
    };

    apiFetch(params).then(() => {
        educationalResourceTypesTable.replaceData(endPointTable);
    });
}
