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

let departmentsTable;
let selectedDepartments = [];
const endPointTable = "/administration/departments/get_departments";

document.addEventListener("DOMContentLoaded", function () {
    initHandlers();
    initializeDepartmentsTable();
    controlReloadTable();
});

function initHandlers() {
    document
        .getElementById("new-department-btn")
        .addEventListener("click", () => {
            newDepartment();
        });

    document
        .getElementById("btn-delete-department")
        .addEventListener("click", () => {
            if (selectedDepartments.length) {
                showModalConfirmation(
                    "Eliminar departamento",
                    "¿Está seguro que desea eliminar las departamentos?",
                    "delete_departments"
                ).then((resultado) => {
                    if (resultado) deleteDepartments();
                });
            } else {
                showToast("Debe seleccionar al menos un departamento", "error");
            }
        });

    document
        .getElementById("department-form")
        .addEventListener("submit", submitDepartmentForm);
}


/**
 * Configuración de la tabla de llamadas utilizando la biblioteca Tabulator.
 * Define las columnas, obtiene los datos del endpoint y maneja la paginación.
 */
function initializeDepartmentsTable() {
    const columns = [
        {
            title: '<div class="checkbox-cell"><input type="checkbox" id="select-all-checkbox"/></div>',
            headerClick: function (e) {
                const selectAllCheckbox = e.target;
                if (selectAllCheckbox.type === "checkbox") {
                    // Asegúrate de que el clic fue en el checkbox
                    departmentsTable.getRows().forEach((row) => {
                        const cell = row.getCell("select");
                        const checkbox = cell
                            .getElement()
                            .querySelector('input[type="checkbox"]');
                        checkbox.checked = selectAllCheckbox.checked;
                        selectedDepartments = updateArrayRecords(
                            checkbox,
                            row.getData(),
                            selectedDepartments
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
                selectedDepartments = updateArrayRecords(
                    checkbox,
                    cell.getRow().getData(),
                    selectedDepartments
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

                const departmentClicked = cell.getRow().getData();
                loadDepartmentModal(departmentClicked.uid);
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
    ];

    departmentsTable = new Tabulator("#departments-table", {
        ajaxURL: endPointTable,
        ...tabulatorBaseConfig,
        ajaxResponse: function (url, params, response) {
            updatePaginationInfo(
                departmentsTable,
                response,
                "departments-table"
            );

            selectedDepartments = [];

            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });

    controlsSearch(departmentsTable, endPointTable, "departments-table");
    controlsPagination(departmentsTable, "departments-table");
}

function newDepartment() {
    resetModal();
    showModal("department-modal", "Añade un departamento");
}

async function loadDepartmentModal(departmentUid) {
    showLoader();

    const params = {
        url: `/administration/departments/get_department/${departmentUid}`,
        method: "GET",
        loader: true,
    };

    apiFetch(params).then((response) => {
        showModal("department-modal", "Edita un departamento");
        fillFormDepartmentModal(response);
    });
}

function fillFormDepartmentModal(department) {
    document.getElementById("department_uid").value = department.uid;
    document.getElementById("name").value = department.name;
}

/**
 * Este bloque maneja la presentación del formulario para una nueva llamada.
 * Recopila los datos y los envía a un endpoint específico.
 * Si la operación tiene éxito, actualiza la tabla y muestra un toast.
}
 */
function submitDepartmentForm() {
    const formData = new FormData(this);

    const params = {
        url: "/administration/departments/save_department",
        body: formData,
        method: "POST",
        loader: true,
        toast: true,
    };

    resetFormErrors("department-modal");

    apiFetch(params)
        .then(() => {
            departmentsTable.setData(endPointTable);
            hideModal("department-modal");
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

/**
 * Elimina los sistemas centros seleccionados.
 * Realiza una petición DELETE al servidor y actualiza la tabla si tiene éxito.
 */
async function deleteDepartments() {
    const params = {
        url: "/administration/departments/delete_departments",
        body: { uids: selectedDepartments.map((department) => department.uid) },
        method: "DELETE",
        loader: true,
        toast: true,
        stringify: true,
    };

    apiFetch(params).then(() => {
        departmentsTable.setData(endPointTable);
        hideModal("department-modal");
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
        departmentsTable.setData(endPointTable);
    });
}

/**
 * Reseteo del modal
 */
function resetModal() {
    const form = document.getElementById("department-form");
    form.reset();
    document.getElementById("department_uid").value = "";
    resetFormErrors();
}
