import { heroicon } from "../heroicons";
import {
    controlsPagination,
    controlsSearch,
    updatePaginationInfo,
    updateArrayRecords,
    tabulatorBaseConfig,
} from "../tabulator_handler";
import { showModal, hideModal, showModalConfirmation } from "../modal_handler";
import { apiFetch } from "../app";
import {
    showFormErrors,
    resetFormErrors,
    getLiveSearchTomSelectInstance,
    updateInputImage,
} from "../app";
import { TabulatorFull as Tabulator } from "tabulator-tables";
import { showToast } from "../toast.js";

let educationalProgramsTable;
let selectedEducationalPrograms = [];
let tomSelectCourses;

const endPointTable =
    "/learning_objects/educational_programs/get_educational_programs";

document.addEventListener("DOMContentLoaded", () => {
    initHandlers();
    initializeEducationalProgramsTable();

    initializeTomSelect();

    updateInputImage();
});

function initHandlers() {
    document
        .getElementById("new-educational-program-btn")
        .addEventListener("click", () => {
            newEducationalProgram();
        });

    document
        .getElementById("educational-program-form")
        .addEventListener("submit", submitFormEducationalProgram);

    document
        .getElementById("btn-delete-educational-programs")
        .addEventListener("click", () => {
            if (selectedEducationalPrograms.length) {
                showModalConfirmation(
                    "Eliminar programas formativos",
                    "¿Está seguro que desea eliminar los programas formativos seleccionados?",
                    "delete_educational_programs"
                ).then((resultado) => {
                    if (resultado) deleteEducationalPrograms();
                });
            } else {
                showToast(
                    "Debe seleccionar al menos una convocatoria",
                    "error"
                );
            }
        });

    document
        .getElementById("btn-reload-table")
        .addEventListener("click", function () {
            reloadTable();
        });
}

function initializeTomSelect() {
    tomSelectCourses = getLiveSearchTomSelectInstance(
        "#select-courses",
        "/learning_objects/educational_programs/search_courses_without_educational_program/",
        function (entry) {
            return {
                value: entry.uid,
                text: entry.title,
            };
        }
    );
}

/**
 * Configuración de la tabla de llamadas utilizando la biblioteca Tabulator.
 * Define las columnas, obtiene los datos del endpoint y maneja la paginación.
 */
function initializeEducationalProgramsTable() {
    const columns = [
        {
            title: '<div class="checkbox-cell"><input type="checkbox" id="select-all-checkbox"/></div>',
            headerClick: function (e) {
                const selectAllCheckbox = e.target;
                if (selectAllCheckbox.type === "checkbox") {
                    // Asegúrate de que el clic fue en el checkbox
                    educationalProgramsTable.getRows().forEach((row) => {
                        const cell = row.getCell("select");
                        const checkbox = cell
                            .getElement()
                            .querySelector('input[type="checkbox"]');
                        checkbox.checked = selectAllCheckbox.checked;
                        selectedEducationalPrograms = updateArrayRecords(
                            checkbox,
                            row.getData(),
                            selectedEducationalPrograms
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

                selectedEducationalPrograms = updateArrayRecords(
                    checkbox,
                    cell.getRow().getData(),
                    selectedEducationalPrograms
                );
            },
            width: 60,
            headerSort: false,
        },
        { title: "Nombre", field: "name" },
        {
            title: "Tipo de programa educativo",
            field: "educational_program_type_name",
        },
        {
            title: "Convocatoria",
            field: "call_name",
        },
        {
            title: "Tipo",
            field: "is_modular",
            formatter: function (cell, formatterParams, onRendered) {
                const isModular = cell.getValue();
                return isModular ? "Modular" : "No modular";
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
                const educationalProgramClicked = cell.getRow().getData();
                loadEducationalProgramModal(educationalProgramClicked.uid);
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
    ];

    educationalProgramsTable = new Tabulator("#educational-programs-table", {
        ajaxURL: endPointTable,
        ...tabulatorBaseConfig,
        ajaxResponse: function (url, params, response) {
            updatePaginationInfo(
                educationalProgramsTable,
                response,
                "educational-resource-types-table"
            );
            selectedEducationalPrograms = [];
            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });

    controlsSearch(
        educationalProgramsTable,
        endPointTable,
        "educational-resource-types-table"
    );

    controlsPagination(
        educationalProgramsTable,
        "educational-resource-types-table"
    );
}

async function loadEducationalProgramModal(educationalProgramUid) {
    const params = {
        url: `/learning_objects/educational_programs/get_educational_program/${educationalProgramUid}`,
        method: "GET",
        loader: true,
    };

    apiFetch(params).then((data) => {
        resetModal();
        fillEducationalProgramModal(data);
        showModal("educational-program-modal", "Editar programa formativo");
    });
}

function fillEducationalProgramModal(educationalProgram) {
    document.getElementById("educational_program_uid").value =
        educationalProgram.uid;
    document.getElementById("name").value = educationalProgram.name;
    document.getElementById("description").value =
        educationalProgram.description;
    document.getElementById("educational_program_type_uid").value =
        educationalProgram.educational_program_type_uid ?? "";
    document.getElementById("call_uid").value =
        educationalProgram.call_uid ?? "";

    document.getElementById("inscription_start_date").value =
        educationalProgram.inscription_start_date;
    document.getElementById("inscription_finish_date").value =
        educationalProgram.inscription_finish_date;

    document.getElementById("is_modular").value = educationalProgram.is_modular;

    if (educationalProgram.image_path) {
        document.getElementById("image_path_preview").src =
            "/" + educationalProgram.image_path;
    } else {
        document.getElementById("image_path_preview").src = defaultImagePreview;
    }

    if (educationalProgram.courses) {
        educationalProgram.courses.forEach((course) => {
            const option = {
                value: course.uid,
                text: course.title,
            };
            tomSelectCourses.addOption(option);
            tomSelectCourses.addItem(option.value);
        });
    }
}

/**
 * Este bloque maneja la presentación del formulario para una nueva llamada.
 * Recopila los datos y los envía a un endpoint específico.
 * Si la operación tiene éxito, actualiza la tabla y muestra un toast.
}
 */
function submitFormEducationalProgram() {
    const formData = new FormData(this);

    const params = {
        url: "/learning_objects/educational_programs/save_educational_program",
        method: "POST",
        body: formData,
        toast: true,
        loader: true,
    };
    resetFormErrors("educational-program-form");

    apiFetch(params)
        .then(() => {
            hideModal("educational-program-modal");
            reloadTable();
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

function newEducationalProgram() {
    resetModal();
    showModal("educational-program-modal", "Crear programa formativo");
}

function resetModal() {
    const formEducationalProgram = document.getElementById(
        "educational-program-form"
    );

    formEducationalProgram.reset();
    tomSelectCourses.clear();
    tomSelectCourses.clearOptions();
    resetFormErrors();
    document.getElementById("educational_program_uid").value = "";
}

/**
 * Elimina programas formativos seleccionados.
 * Realiza una petición DELETE al servidor y actualiza la tabla si tiene éxito.
 */
async function deleteEducationalPrograms() {
    const params = {
        url: "/learning_objects/educational_programs/delete_educational_programs",
        method: "DELETE",
        body: { uids: selectedEducationalPrograms.map((e) => e.uid) },
        toast: true,
        loader: true,
        stringify: true,
    };

    apiFetch(params).then(() => {
        reloadTable;
    });
}

function reloadTable() {
    educationalProgramsTable.replaceData(endPointTable);
}
