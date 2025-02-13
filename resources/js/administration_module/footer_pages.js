import { getCsrfToken, showFormErrors, resetFormErrors, apiFetch, instanceTinymce } from "../app.js";
import { TabulatorFull as Tabulator } from "tabulator-tables";
import {
    controlsPagination,
    updatePaginationInfo,
    tabulatorBaseConfig,
    controlsSearch,
    updateArrayRecords,
    formatDateTime,
} from "../tabulator_handler.js";
import { heroicon } from "../heroicons.js";
import { showToast } from "../toast.js";
import {
    hideModal,
    showModal,
    showModalConfirmation,
} from "../modal_handler.js";

let filters = [];
let footerPagesTable;
let tinymceContent;
const endPointTable = "/administration/footer_pages/get_footer_pages";
const footerPagesTableId = "footer-pages-table";

let selectedFooterPages = [];

document.addEventListener("DOMContentLoaded", function () {
    initHandlers();
});

function initHandlers() {
    tinymceContent = instanceTinymce("#footer-page-content");

    document
        .getElementById("new-footer-page-btn")
        .addEventListener("click", () => {
            newFooterPage();
        });

    document
        .getElementById("footer-page-form")
        .addEventListener("submit", submitFooterPageForm);

    document.getElementById("delete-footer-pages-btn").addEventListener("click", () => {
        if (selectedFooterPages.length) {
            showModalConfirmation(
                "Eliminar páginas de footer",
                "¿Está seguro que desea eliminar las páginas de footer seleccionadas?",
                "delete_footer_pages"
            ).then((resultado) => {
                if (resultado) deleteFooterPages();
            });
        } else {
            showToast(
                "Debe seleccionar al menos una página de footer",
                "error"
            );
        }
    });

    document.getElementById("btn-reload-table").addEventListener("click", function () {
        reloadTable();
    });

    initializeLegalTextsPagesTable();
    controlsSearch(footerPagesTable, endPointTable, footerPagesTableId);

}

function newFooterPage() {
    resetModal();
    showModal("footer-page-modal", "Nueva página de footer");
}

function resetModal() {
    document.getElementById("footer_page_uid").value = "";
    document.getElementById("name").value = "";
    tinymce.get("footer-page-content").setContent("");
    document.getElementById("version").value = "";
    document.getElementById("slug").value = "";
}

function initializeLegalTextsPagesTable() {
    const columns = [
        {
            title: '<div class="checkbox-cell"><input type="checkbox" id="select-all-checkbox"/></div>',
            headerClick: function (e) {
                const selectAllCheckbox = e.target;
                if (selectAllCheckbox.type === "checkbox") {
                    // Asegúrate de que el clic fue en el checkbox
                    footerPagesTable.getRows().forEach((row) => {
                        const cell = row.getCell("select");
                        const checkbox = cell
                            .getElement()
                            .querySelector('input[type="checkbox"]');
                        checkbox.checked = selectAllCheckbox.checked;

                        selectedFooterPages = updateArrayRecords(
                            checkbox,
                            row.getData(),
                            selectedFooterPages
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
                const checkbox = e.target;

                selectedFooterPages = updateArrayRecords(
                    checkbox,
                    cell.getRow().getData(),
                    selectedFooterPages
                );
            },
            headerSort: false,
            width: 60,
        },
        { title: "Nombre", field: "name", widthGrow: 4 },
        { title: "Slug", field: "slug", widthGrow: 4 },
        {
            title: "Fecha de creación",
            field: "created_at",
            formatter: function (cell, formatterParams, onRendered) {
                const dateCreation = cell.getRow().getData().created_at;

                if (dateCreation) return formatDateTime(dateCreation);
                else return "";
            },
            widthGrow: 2,
        },
        { title: "Versión", field: "version", widthGrow: 2 },
        {
            title: "Aceptación requerida",
            field: "acceptance_required",
            formatter: function (cell, formatterParams, onRendered) {
                const data = cell.getRow().getData().acceptance_required;

                if (data == 0) return "No";
                else return "Si";
            },
            widthGrow: 2,
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

                const footerPageClicked = cell.getRow().getData();
                loadLegalPageModal(footerPageClicked.uid);
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
    ];

    const { ajaxConfig, ...tabulatorBaseConfigOverrided } = tabulatorBaseConfig;

    footerPagesTable = new Tabulator("#" + footerPagesTableId, {
        ...tabulatorBaseConfigOverrided,
        ajaxURL: endPointTable,
        ajaxConfig: {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": getCsrfToken(),
            },
        },
        ajaxParams: {
            filters: {
                ...filters,
            },
        },
        ajaxResponse: function (url, params, response) {
            controlsPagination(footerPagesTable, footerPagesTableId);
            updatePaginationInfo(
                footerPagesTable,
                response,
                footerPagesTableId
            );

            document.getElementById("select-all-checkbox").checked = false;

            selectedFooterPages = [];

            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });
}

function loadLegalPageModal(legalPageUid) {
    const params = {
        url: "/administration/footer_pages/get_footer_page/" + legalPageUid,
        method: "GET",
        loader: true
    };

    apiFetch(params).then((data) => {
        fillFormFooterPage(data);
        showModal("footer-page-modal", "Editar página de footer");
    });
}

function submitFooterPageForm() {
    const formData = new FormData(this);

    const content = tinymceContent.getContent();

    formData.append("content", content);

    const checkbox = document.getElementById('acceptance_required');

    if (checkbox.checked){
        formData.append("acceptance_required", 1);
    }else{
        formData.append("acceptance_required", 0);
    }

    resetFormErrors("footer-page-form");

    const params = {
        url: "/administration/footer_pages/save_footer_page",
        method: "POST",
        body: formData,
        toast: true,
        loader: true,
    };

    apiFetch(params).then(() => {
        hideModal("footer-page-modal");
        footerPagesTable.setData(endPointTable);
    }).catch((data) => {
        showFormErrors(data.errors);
    });
}

function fillFormFooterPage(footerPage) {
    document.getElementById("footer_page_uid").value = footerPage.uid;
    document.getElementById("name").value = footerPage.name;
    tinymce.get("footer-page-content").setContent(footerPage.content);
    document.getElementById("slug").value = footerPage.slug;
    document.getElementById("version").value = footerPage.version;
    const checkbox = document.getElementById("acceptance_required");
    if (footerPage.acceptance_required == 1){
        checkbox.checked = true;
    }else{
        checkbox.checked = false;
    }
}

/**
 * Elimina las páginas de footer seleccionadas.
 * Realiza una petición DELETE al servidor y actualiza la tabla si tiene éxito.
 */
async function deleteFooterPages() {
    const params = {
        url: "/administration/footer_pages/delete_footer_pages",
        method: "DELETE",
        body: { uids: selectedFooterPages.map((footerPage) => footerPage.uid) },
        toast: true,
        loader: true,
        stringify: true,
    };

    apiFetch(params).then(() => {
        reloadTable();
    });
}

function reloadTable() {
    footerPagesTable.setData(endPointTable);
}
