import { showModal, showModalConfirmation, hideModal } from "../modal_handler";
import { heroicon } from "../heroicons";
import {
    controlsPagination,
    updateArrayRecords,
    tabulatorBaseConfig,
    updatePaginationInfo,
    controlsSearch,
} from "../tabulator_handler";
import { showFormErrors, resetFormErrors, apiFetch } from "../app.js";
import { showToast } from "../toast.js";

import { TabulatorFull as Tabulator } from "tabulator-tables";

let redirectionQueriesTable;
let selectedRedirectionQueries = [];
const endPointTable =
    "/administration/redirection_queries_learning_objects/get_redirections_queries";

document.addEventListener("DOMContentLoaded", function () {
    initHandlers();
    initializeRedirectionQueriesTable();
});

function initHandlers() {
    document
        .getElementById("new-redirection-query-btn")
        .addEventListener("click", newRedirectionQuery);

    document
        .getElementById("btn-reload-table")
        .addEventListener("click", function () {
            redirectionQueriesTable.setData(endPointTable);
        });

    document.addEventListener("click", function (event) {
        // Botón eliminar de una redirección de consulta
        if (event.target.matches(".delete-redirection-query-btn")) {
            const parentRow = event.target.closest("tr");
            const uid = parentRow.getAttribute("data-uid");

            deleteRedirectionQueryProgramType(uid);
        }
    });

    document
        .getElementById("btn-delete-redirection-queries")
        .addEventListener("click", () => {
            if (selectedRedirectionQueries.length) {
                showModalConfirmation(
                    "Eliminar Redirecciones",
                    "¿Está seguro que desea eliminar las redirecciones seleccionadas?",
                    "delete_redirection_queries"
                ).then((resultado) => {
                    if (resultado) {
                        deleteRedirectionQueries();
                    }
                });
            } else {
                showToast("Debe seleccionar al menos una redirección", "error");
            }
        });

    document
        .getElementById("redirection-query-form")
        .addEventListener("submit", submitNewRedirectionQuery);

    document
        .getElementById("learning_object_type")
        .addEventListener("change", function () {
            const value = this.value;
            controlObjectType(value);
        });
}

function newRedirectionQuery() {
    resetForm();
    showModal("redirection-query-modal", "Añade una redirección");
}

function resetForm() {
    const form = document.getElementById("redirection-query-form");
    resetFormErrors("redirection-query-form");
    form.reset();
    controlObjectType(null);
    document.getElementById("redirection_query_uid").value = "";
}

function initializeRedirectionQueriesTable() {
    const columns = [
        {
            title: '<div class="checkbox-cell"><input type="checkbox" id="select-all-checkbox"/></div>',
            headerClick: function (e) {
                const selectAllCheckbox = e.target;
                if (selectAllCheckbox.type === "checkbox") {
                    // Asegúrate de que el clic fue en el checkbox
                    redirectionQueriesTable.getRows().forEach((row) => {
                        const cell = row.getCell("select");
                        const checkbox = cell
                            .getElement()
                            .querySelector('input[type="checkbox"]');
                        checkbox.checked = selectAllCheckbox.checked;
                        selectedRedirectionQueries = updateArrayRecords(
                            checkbox,
                            row.getData(),
                            selectedRedirectionQueries
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

                selectedRedirectionQueries = updateArrayRecords(
                    checkbox,
                    rowData,
                    selectedRedirectionQueries
                );
            },
            headerSort: false,
            width: 60,
        },
        { title: "Tipo", field: "type", widthGrow: "2" },
        {
            title: "Tipo de objeto",
            field: "learning_object_type",
            widthGrow: "2",
            formatter: function (cell, formatterParams, onRendered) {
                const value = cell.getValue();
                return value === "COURSE" ? "Curso" : "Programas educativo";
            },
        },
        { title: "Contacto", field: "contact", widthGrow: "4" },
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
                const redirectionQueryClicked = cell.getRow().getData();
                loadRedirectionQueryModal(redirectionQueryClicked.uid);
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
    ];

    redirectionQueriesTable = new Tabulator("#redirection-queries-table", {
        ajaxURL: endPointTable,
        ...tabulatorBaseConfig,
        ajaxResponse: function (url, params, response) {
            updatePaginationInfo(
                redirectionQueriesTable,
                response,
                "redirection-queries-table"
            );

            selectedRedirectionQueries = [];

            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });

    controlsPagination(redirectionQueriesTable, "redirection-queries-table");

    controlsSearch(
        redirectionQueriesTable,
        endPointTable,
        "redirection-queries-table"
    );
}

async function loadRedirectionQueryModal(redirectionQueryUid) {
    const params = {
        url: `/administration/redirection_queries_learning_objects/get_redirection_queries/${redirectionQueryUid}`,
        method: "GET",
        loader: true,
    };

    resetFormErrors("redirection-query-form");
    apiFetch(params).then((data) => {
        fillRedirectionQueryModal(data);
        showModal("redirection-query-modal", "Edita una redirección");
    });
}

function fillRedirectionQueryModal(redirectionQuery) {
    document.getElementById("learning_object_type").value =
        redirectionQuery.learning_object_type;
    controlObjectType(redirectionQuery.learning_object_type);
    document.getElementById("educational_program_type_uid").value =
        redirectionQuery.educational_program_type_uid ?? "";
    document.getElementById("course_type_uid").value =
        redirectionQuery.course_type_uid ?? "";
    document.getElementById("type").value = redirectionQuery.type;
    document.getElementById("contact").value = redirectionQuery.contact;
    document.getElementById("redirection_query_uid").value =
        redirectionQuery.uid;
}

function controlObjectType(objectType) {
    const courseTypeContainer = document.getElementById(
        "course-type-container"
    );

    const educationalProgramContainer = document.getElementById(
        "educational-program-type-container"
    );

    if (objectType == "COURSE") {
        courseTypeContainer.classList.remove("hidden");
        educationalProgramContainer.classList.add("hidden");
    } else if (objectType == "EDUCATIONAL_PROGRAM") {
        courseTypeContainer.classList.add("hidden");
        educationalProgramContainer.classList.remove("hidden");
    } else {
        courseTypeContainer.classList.add("hidden");
        educationalProgramContainer.classList.add("hidden");
    }
}

async function deleteRedirectionQueries() {
    const params = {
        url: "/administration/redirection_queries_learning_objects/delete_redirections_queries",
        method: "DELETE",
        body: {
            uids: selectedRedirectionQueries.map(
                (redirectionQuery) => redirectionQuery.uid
            ),
        },
        stringify: true,
        loader: true,
        toast: true,
    };

    apiFetch(params).then(() => {
        redirectionQueriesTable.replaceData(endPointTable);
    });
}

/**
 * Este bloque maneja la presentación del formulario para una nueva redirección.
 * Recopila los datos y los envía a un endpoint específico.
 * Si la operación tiene éxito, actualiza la tabla y muestra un toast.
 */
function submitNewRedirectionQuery() {
    const formData = new FormData(this);

    const params = {
        url: "/administration/redirection_queries_learning_objects/save_redirection_query",
        method: "POST",
        body: formData,
        toast: true,
        loader: true,
    };

    resetFormErrors("redirection-query-form");

    apiFetch(params)
        .then(() => {
            redirectionQueriesTable.replaceData(endPointTable);
            hideModal("redirection-query-modal");
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}
