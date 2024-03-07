import { heroicon } from "../heroicons.js";
import { hideModal, showModal, showModalConfirmation } from "../modal_handler";
import {
    getCsrfToken,
    showFormErrors,
    resetFormErrors,
    showLoader,
    hideLoader,
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

const endPointTable =
    "/cataloging/educational_program_types/get_list_educational_program_types";
let educationalProgramTypesTable;

let selectedEducationalProgramTypes = [];

document.addEventListener("DOMContentLoaded", function () {
    initHandlers();
    initializeEducationalProgramTypesTable();
    controlsSearch(
        educationalProgramTypesTable,
        endPointTable,
        "educational-program-types-table"
    );
    controlsPagination(
        educationalProgramTypesTable,
        "educational-program-types-table"
    );
    handleDeleteEducationalProgramTypes();
});

function initHandlers() {
    document
        .getElementById("add-educational-program-type-btn")
        .addEventListener("click", function () {
            newEducationalProgramType();
        });

    document
        .getElementById("educational-program-type-form")
        .addEventListener("submit", submitFormEducationalProgramTypeModal);
}

function newEducationalProgramType() {
    resetModal();
    showModal(
        "educational-program-type-modal",
        "Añade un nuevo tipo de programa educativo"
    );
}

function initializeEducationalProgramTypesTable() {
    const columns = [
        {
            title: '<div class="checkbox-cell"><input type="checkbox" id="select-all-checkbox"/></div>',
            headerClick: function (e) {
                const selectAllCheckbox = e.target;
                if (selectAllCheckbox.type === "checkbox") {
                    // Asegúrate de que el clic fue en el checkbox
                    educationalProgramTypesTable.getRows().forEach((row) => {
                        const cell = row.getCell("select");
                        const checkbox = cell
                            .getElement()
                            .querySelector('input[type="checkbox"]');
                        checkbox.checked = selectAllCheckbox.checked;
                        selectedEducationalProgramTypes = updateArrayRecords(
                            checkbox,
                            row.getData(),
                            selectedEducationalProgramTypes
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

                selectedEducationalProgramTypes = updateArrayRecords(
                    checkbox,
                    rowData,
                    selectedEducationalProgramTypes
                );
            },
            headerSort: false,
            width: 60,
        },
        { title: "Nombre", field: "name" },
        { title: "Descripción", field: "description" },
        {
            title: "Gestores pueden emitir credenciales",
            field: "managers_can_emit_credentials",
            formatter: function (cell, formatterParams, onRendered) {
                const data = cell.getRow().getData();
                return data.managers_can_emit_credentials ? "Sí" : "No";
            },
        },
        {
            title: "Profesores pueden emitir credenciales",
            field: "teachers_can_emit_credentials",
            formatter: function (cell, formatterParams, onRendered) {
                const data = cell.getRow().getData();
                return data.teachers_can_emit_credentials ? "Sí" : "No";
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
                const educationalProgramTypeClicked = cell.getRow().getData();
                loadEducationalProgramTypeModal(
                    educationalProgramTypeClicked.uid
                );
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
    ];

    educationalProgramTypesTable = new Tabulator(
        "#educational-program-types-table",
        {
            ajaxURL: endPointTable,
            ...tabulatorBaseConfig,
            ajaxResponse: function (url, params, response) {
                updatePaginationInfo(
                    educationalProgramTypesTable,
                    response,
                    "educational-program-types-table"
                );

                selectedEducationalProgramTypes = [];

                return {
                    last_page: response.last_page,
                    data: response.data,
                };
            },
            columns: columns,
        }
    );
}

async function loadEducationalProgramTypeModal(uid) {
    const params = {
        url: `/cataloging/educational_program_types/get_educational_program_type/${uid}`,
        method: "GET",
        loader: true,
    };

    apiFetch(params).then((data) => {
        resetModal();
        fillFormEducationalProgramTypeModal(data);
        showModal(
            "educational-program-type-modal",
            "Edita el tipo de programa educativo"
        );
    });
}

function fillFormEducationalProgramTypeModal(educational_program_type) {
    document.getElementById("name").value = educational_program_type.name;

    document.getElementById("description").value =
        educational_program_type.description;

    document.getElementById("educational_program_type_uid").value =
        educational_program_type.uid;

    document.getElementById("managers_can_emit_credentials").checked =
        educational_program_type.managers_can_emit_credentials ? true : false;

    document.getElementById("teachers_can_emit_credentials").checked =
        educational_program_type.teachers_can_emit_credentials ? true : false;
}

/**
 * Envía el formulario del modal de tipo de programa educativo.
 * Realiza una petición POST al servidor con los datos del formulario.
 */
function submitFormEducationalProgramTypeModal() {
    const formData = new FormData(this);

    resetFormErrors();

    formData.append(
        "managers_can_emit_credentials",
        managers_can_emit_credentials.checked
    );
    formData.append(
        "teachers_can_emit_credentials",
        teachers_can_emit_credentials.checked
    );

    const params = {
        url: "/cataloging/educational_program_types/save_educational_program_type",
        method: "POST",
        body: formData,
        loader: true,
        toast: true,
    };

    apiFetch(params)
        .then(() => {
            educationalProgramTypesTable.replaceData(endPointTable);
            hideModal("educational-program-type-modal");
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

function resetModal() {
    const form = document.getElementById("educational-program-type-form");
    form.reset();
    // Reseteo del campo uid ya que al ser hidden, no le afecta form.reset()
    // Reseteamos solo este campo y no todos los hidden porque si no nos cargamos el campo del token csrf
    document.getElementById("educational_program_type_uid").value = "";

    resetFormErrors();
}

/**
 * Maneja la eliminación de tipos de programa educativo.
 * Muestra un modal de confirmación y luego llama a deleteEducationalProgramTypes si se confirma.
 */
function handleDeleteEducationalProgramTypes() {
    document
        .getElementById("delete-educational-program-type-btn")
        .addEventListener("click", () => {
            if (selectedEducationalProgramTypes.length) {
                showModalConfirmation(
                    "Eliminar tipos de programa educativos",
                    "¿Está seguro que desea eliminar los tipos de programa educativo seleccionados?",
                    "delete_educational_program_types"
                ).then((resultado) => {
                    if (resultado) {
                        deleteEducationalProgramTypes();
                    }
                });
            } else {
                showToast(
                    "Debe seleccionar al menos un tipo de programa educativo",
                    "error"
                );
            }
        });
}

/**
 * Elimina tipos de programa educativo seleccionados.
 * Realiza una petición DELETE al servidor y actualiza la tabla si tiene éxito.
 */
async function deleteEducationalProgramTypes() {
    showLoader();
    await fetch(
        "/cataloging/educational_program_types/delete_educational_program_types",
        {
            method: "DELETE",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": getCsrfToken(),
            },
            body: JSON.stringify({
                uids: selectedEducationalProgramTypes.map((type) => type.uid),
            }),
        }
    )
        .then(async (response) => {
            const data = await response.json();
            if (response.status === 200) {
                showToast(data.message, "success");

                educationalProgramTypesTable.replaceData(endPointTable);
            } else if (response.status === 400) {
                showToast(data.message, "error");
            }
        })
        .catch((error) => {
            showToast(defaultErrorMessageFetch, "error");

            console.error("Hubo un problema con la operación fetch:", error);
        });

    hideLoader();
}
