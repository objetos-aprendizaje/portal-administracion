import { heroicon } from "../heroicons.js";
import { hideModal, showModal, showModalConfirmation } from "../modal_handler";
import {
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

const endPointTable = "/cataloging/course_types/get_list_course_types";
let courseTypesTable;

let selectedCourseTypes = [];

document.addEventListener("DOMContentLoaded", function () {
    initHandlers();

    initializeCourseTypesTable();

});

function initHandlers() {
    document
        .getElementById("add-course-type-btn")
        .addEventListener("click", function () {
            newCourseType();
        });

    document
        .getElementById("delete-course-type-btn")
        .addEventListener("click", () => {
            if (selectedCourseTypes.length) {
                showModalConfirmation(
                    "Eliminar tipos de curso",
                    "¿Está seguro que desea eliminar los tipos de curso seleccionados?",
                    "delete_course_types"
                ).then((resultado) => {
                    if (resultado) {
                        deleteCourseTypes();
                    }
                });
            } else {
                showToast(
                    "Debe seleccionar al menos un tipo de curso",
                    "error"
                );
            }
        });

    document
        .getElementById("course-type-form")
        .addEventListener("submit", submitFormCourseTypeModal);
}

function newCourseType() {
    resetModal();
    showModal("course-type-modal", "Añade un nuevo tipo de curso");
}

function initializeCourseTypesTable() {
    const columns = [
        {
            title: '<div class="checkbox-cell"><input type="checkbox" id="select-all-checkbox"/></div>',
            headerClick: function (e) {
                const selectAllCheckbox = e.target;
                if (selectAllCheckbox.type === "checkbox") {
                    // Asegúrate de que el clic fue en el checkbox
                    courseTypesTable.getRows().forEach((row) => {
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
                const courseTypeClicked = cell.getRow().getData();
                loadCourseTypeModal(courseTypeClicked.uid);
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
    ];

    courseTypesTable = new Tabulator("#course-types-table", {
        ajaxURL: endPointTable,
        ...tabulatorBaseConfig,
        ajaxResponse: function (url, params, response) {
            updatePaginationInfo(
                courseTypesTable,
                response,
                "course-types-table"
            );

            selectedCourseTypes = [];

            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });

    controlsSearch(courseTypesTable, endPointTable, "course-types-table");
    controlsPagination(courseTypesTable, "course-types-table");
}

async function loadCourseTypeModal(uid) {
    const params = {
        url: `/cataloging/course_types/get_course_type/${uid}`,
        method: "GET",
        loader: true,
    };

    apiFetch(params).then((data) => {
        fillFormCourseTypeModal(data);
        showModal("course-type-modal", "Edita el tipo de curso");
    });
}

function fillFormCourseTypeModal(course_type) {
    document.getElementById("name").value = course_type.name;
    document.getElementById("description").value = course_type.description;
    document.getElementById("course_type_uid").value = course_type.uid;
}

/**
 * Envía el formulario del modal de tipo de curso.
 * Realiza una petición POST al servidor con los datos del formulario.
 */
function submitFormCourseTypeModal() {
    const formData = new FormData(this);
    const params = {
        url: "/cataloging/course_types/save_course_type",
        method: "POST",
        body: formData,
        loader: true,
        toast: true,
    };

    apiFetch(params).then(() => {
        resetModal();
        courseTypesTable.replaceData(endPointTable);
        hideModal("course-type-modal");
    });
}

function resetModal() {
    const form = document.getElementById("course-type-form");
    form.reset();
    document.getElementById("course_type_uid").value = "";

    resetFormErrors();
}

/**
 * Elimina tipos de curso seleccionados.
 * Realiza una petición DELETE al servidor y actualiza la tabla si tiene éxito.
 */
async function deleteCourseTypes() {
    const params = {
        url: "/cataloging/course_types/delete_course_types",
        method: "DELETE",
        body: {
            uids: selectedCourseTypes.map((type) => type.uid),
        },
        loader: true,
        toast: true,
        stringify: true,
    };

    apiFetch(params).then(() => {
        courseTypesTable.replaceData(endPointTable);
    });
}
