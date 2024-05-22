import { heroicon } from "../heroicons.js";
import { hideModal, showModal, showModalConfirmation } from "../modal_handler";
import {
    showFormErrors,
    resetFormErrors,
    apiFetch,
} from "../app.js";
import {
    controlsPagination,
    updateArrayRecords,
    tabulatorBaseConfig,
    updatePaginationInfo,
    controlsSearch,
} from "../tabulator_handler";
import { TabulatorFull as Tabulator } from "tabulator-tables";
import { showToast } from "../toast.js";

import Choices from "choices.js";
const endPointTable =
    "/cataloging/certification_types/get_list_certification_types";
let certificationTypesTable;

let selectedCourseTypes = [];
let categoryChoices;

document.addEventListener("DOMContentLoaded", function () {
    initHandlers();
    instanceCategoryChoices();
    initializeCourseTypesTable();

});

function initHandlers() {
    document
        .getElementById("add-certification-type-btn")
        .addEventListener("click", function () {
            newCertificationType();
        });

    document
        .getElementById("certification-type-form")
        .addEventListener("submit", submitFormCertificationTypeModal);

    document
        .getElementById("delete-certification-type-btn")
        .addEventListener("click", () => {
            if (selectedCourseTypes.length) {
                showModalConfirmation(
                    "Eliminar tipos de certificación",
                    "¿Está seguro que desea eliminar los tipos de certificación seleccionados?",
                    "delete_certification_types"
                ).then((resultado) => {
                    if (resultado) {
                        deleteCourseTypes();
                    }
                });
            } else {
                showToast(
                    "Debe seleccionar al menos un tipo de certificación",
                    "error"
                );
            }
        });
}

function instanceCategoryChoices() {
    const categorySelect = document.getElementById("category_uid");

    categoryChoices = new Choices(categorySelect, {
        removeItemButton: false,
        searchEnabled: true,
        itemSelectText: "",
        allowHTML: true,
    });
}

function newCertificationType() {
    resetModal();
    showModal(
        "certification-type-modal",
        "Añade un nuevo tipo de certificación"
    );
}

function initializeCourseTypesTable() {
    const columns = [
        {
            title: '<div class="checkbox-cell"><input type="checkbox" id="select-all-checkbox"/></div>',
            headerClick: function (e) {
                const selectAllCheckbox = e.target;
                if (selectAllCheckbox.type === "checkbox") {
                    // Asegúrate de que el clic fue en el checkbox
                    certificationTypesTable.getRows().forEach((row) => {
                        const cell = row.getCell("select");
                        const checkbox = cell
                            .getElement()
                            .querySelector('input[type="checkbox"]');
                        checkbox.checked = selectAllCheckbox.checked;
                        selectedCourseTypes = updateArrayRecords(
                            checkbox,
                            row.getData(),
                            selectedCourseTypes
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

                selectedCourseTypes = updateArrayRecords(
                    checkbox,
                    rowData,
                    selectedCourseTypes
                );
            },
            headerSort: false,
            width: 60,
        },
        { title: "Nombre", field: "name", widthGrow: 5 },
        { title: "Descripción", field: "description", widthGrow: 5 },
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
                const certificationTypeClicked = cell.getRow().getData();
                loadCourseTypeModal(certificationTypeClicked.uid);
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
    ];

    certificationTypesTable = new Tabulator("#certification-types-table", {
        ajaxURL: endPointTable,
        ...tabulatorBaseConfig,
        ajaxResponse: function (url, params, response) {
            updatePaginationInfo(
                certificationTypesTable,
                response,
                "certification-types-table"
            );

            selectedCourseTypes = [];

            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });

    controlsSearch(
        certificationTypesTable,
        endPointTable,
        "certification-types-table"
    );

    controlsPagination(certificationTypesTable, "certification-types-table");
}

async function loadCourseTypeModal(uid) {

    const params = {
        url: `/cataloging/certification_types/get_certification_type/${uid}`,
        method: "GET",
        loader: true,
    };

    apiFetch(params).then((data) => {
        fillFormCourseTypeModal(data);
        showModal("certification-type-modal");
    });
}

function fillFormCourseTypeModal(certification_type) {
    document.getElementById("certification-type-modal-title").textContent =
        "Edita el tipo de certificación";

    document.getElementById("name").value = certification_type.name;

    document.getElementById("description").value =
        certification_type.description;

    categoryChoices.setChoiceByValue(certification_type.category_uid);
    document.getElementById("certification_type_uid").value =
        certification_type.uid;
}

/**
 * Envía el formulario del modal de tipo de certificación.
 * Realiza una petición POST al servidor con los datos del formulario.
 */
function submitFormCertificationTypeModal() {


    resetFormErrors("certification-type-form");

    const formData = new FormData(this);

    const params = {
        url: "/cataloging/certification_types/save_certification_type",
        method: "POST",
        body: formData,
        toast: true,
        loader: true,
    };
    apiFetch(params)
        .then(() => {
            certificationTypesTable.replaceData(endPointTable);
            hideModal("certification-type-modal");
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

function resetModal() {
    const form = document.getElementById("certification-type-form");
    // Reseteo del campo uid ya que al ser hidden, no le afecta form.reset()
    // Reseteamos solo este campo y no todos los hidden porque si no nos cargamos el campo del token csrf
    document.getElementById("certification_type_uid").value = "";
    form.reset();

    document.getElementById("name").value = "";
    document.getElementById("description").value = "";
    categoryChoices.setChoiceByValue("");
    resetFormErrors();
}

/**
 * Elimina tipos de certificación seleccionados.
 * Realiza una petición DELETE al servidor y actualiza la tabla si tiene éxito.
 */
async function deleteCourseTypes() {
    const params = {
        url: "/cataloging/certification_types/delete_certification_types",
        method: "DELETE",
        body: { uids: selectedCourseTypes.map((type) => type.uid) },
        toast: true,
        loader: true,
        stringify: true
    };

    apiFetch(params).then(() => {
        certificationTypesTable.replaceData(endPointTable);
    });
}
