import { getCsrfToken, showFormErrors, resetFormErrors, apiFetch } from "../app.js";
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
let headerPagesTable;
let tinymceContent;
const endPointTable = "/administration/header_pages/get_header_pages";
const headerPagesTableId = "header-pages-table";

let selectedHeaderPages = [];

document.addEventListener("DOMContentLoaded", function () {
    initHandlers();
});

function initHandlers() {
    tinymce.init({
        base_url: "/dist/tinymce",
        selector: "#header-page-content",
        promotion: false,
        branding: false,
        language: "es",
        language_url: "../langs/tinymce_lang_spanish.js",
        setup: function (editor) {
            tinymceContent = editor;
        },
    });

    document
        .getElementById("new-header-page-btn")
        .addEventListener("click", () => {
            newHeaderPage();
        });

    document
        .getElementById("header-page-form")
        .addEventListener("submit", submitHeaderPageForm);

    document.getElementById("delete-header-pages-btn").addEventListener("click", () => {
        if (selectedHeaderPages.length) {
            showModalConfirmation(
                "Eliminar páginas de header",
                "¿Está seguro que desea eliminar las páginas de header seleccionadas?",
                "delete_header_pages"
            ).then((resultado) => {
                if (resultado) deleteHeaderPages();
            });
        } else {
            showToast(
                "Debe seleccionar al menos una página de header",
                "error"
            );
        }
    });

    document.getElementById("btn-reload-table").addEventListener("click", function () {
        reloadTable();
    });

    initializeLegalTextsPagesTable();
    controlsSearch(headerPagesTable, endPointTable, headerPagesTableId);

}

function newHeaderPage() {
    resetModal();
    showModal("header-page-modal", "Nueva página de header");
}

function resetModal() {
    document.getElementById("header_page_uid").value = "";
    document.getElementById("name").value = "";
    tinymce.get("header-page-content").setContent("");
    document.getElementById("slug").value = "";
    document.getElementById("order").value = "";
    getAllParentPages();
}

function initializeLegalTextsPagesTable() {
    const columns = [
        {
            title: '<div class="checkbox-cell"><input type="checkbox" id="select-all-checkbox"/></div>',
            headerClick: function (e) {
                const selectAllCheckbox = e.target;
                if (selectAllCheckbox.type === "checkbox") {
                    // Asegúrate de que el clic fue en el checkbox
                    headerPagesTable.getRows().forEach((row) => {
                        const cell = row.getCell("select");
                        const checkbox = cell
                            .getElement()
                            .querySelector('input[type="checkbox"]');
                        checkbox.checked = selectAllCheckbox.checked;

                        selectedHeaderPages = updateArrayRecords(
                            checkbox,
                            row.getData(),
                            selectedHeaderPages
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

                selectedHeaderPages = updateArrayRecords(
                    checkbox,
                    cell.getRow().getData(),
                    selectedHeaderPages
                );
            },
            headerSort: false,
            width: 60,
        },
        { title: "Nombre", field: "name", widthGrow: 4 },
        { title: "Slug", field: "slug", widthGrow: 4 },
        { title: "Orden", field: "order", widthGrow: 2 },
        {
            title: "Padre",
            field: "parent",
            formatter: function (cell, formatterParams, onRendered) {
                if (cell.getRow().getData().parent_page_name != null){
                    const dateCreation = cell.getRow().getData().parent_page_name.name;

                    if (dateCreation) return dateCreation;
                    else return "";
                }

            },
            widthGrow: 2,
        },
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

                const headerPageClicked = cell.getRow().getData();
                loadLegalPageModal(headerPageClicked.uid);
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
    ];

    const { ajaxConfig, ...tabulatorBaseConfigOverrided } = tabulatorBaseConfig;

    headerPagesTable = new Tabulator("#" + headerPagesTableId, {
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
            controlsPagination(headerPagesTable, headerPagesTableId);
            updatePaginationInfo(
                headerPagesTable,
                response,
                headerPagesTableId
            );

            document.getElementById("select-all-checkbox").checked = false;

            selectedHeaderPages = [];

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
        url: "/administration/header_pages/get_header_page/" + legalPageUid,
        method: "GET",
        loader: true
    };

    apiFetch(params).then((data) => {
        fillFormHeaderPage(data);
        showModal("header-page-modal", "Editar página de header");
    });
}

function submitHeaderPageForm() {
    const formData = new FormData(this);

    const content = tinymceContent.getContent();

    formData.append("content", content);

    resetFormErrors("header-page-form");

    const params = {
        url: "/administration/header_pages/save_header_page",
        method: "POST",
        body: formData,
        toast: true,
        loader: true,
    };

    apiFetch(params).then(() => {
        hideModal("header-page-modal");
        headerPagesTable.setData(endPointTable);
    }).catch((data) => {
        showFormErrors(data.errors);
    });
}

function fillFormHeaderPage(headerPage) {

    document.getElementById("header_page_uid").value = headerPage.uid;
    document.getElementById("name").value = headerPage.name;
    tinymce.get("header-page-content").setContent(headerPage.content);
    document.getElementById("slug").value = headerPage.slug;
    document.getElementById("order").value = headerPage.order;
    getAllParentPages(headerPage.header_page_uid);
}

/**
 * Elimina las páginas de header seleccionadas.
 * Realiza una petición DELETE al servidor y actualiza la tabla si tiene éxito.
 */
async function deleteHeaderPages() {
    const params = {
        url: "/administration/header_pages/delete_header_pages",
        method: "DELETE",
        body: { uids: selectedHeaderPages.map((headerPage) => headerPage.uid) },
        toast: true,
        loader: true,
        stringify: true,
    };

    apiFetch(params).then(() => {
        reloadTable();
    });
}

function reloadTable() {
    headerPagesTable.setData(endPointTable);
}
function getAllParentPages(uid = null){

    const params = {
        url: "/administration/header_pages/get_header_pages_select/",
        method: "GET",
    };

    apiFetch(params).then((data) => {
        fillSelectParentPages(data, uid);
    });

}

function fillSelectParentPages(data, uid){

    const selectCombo = document.getElementById("parent_page_uid");

    selectCombo.innerHTML = "";

    const emptyOption = document.createElement('option');
    emptyOption.value = ''; // Valor vacío
    emptyOption.textContent = 'Selecciona una opción si va a crear una subpágina'; // Texto que se mostrará como opción vacía

    // Agregar opción vacía al select
    selectCombo.appendChild(emptyOption);

    data.forEach(page => {
        // Crear opción
        const option = document.createElement('option');
        option.value = page.uid; // Valor del option es el uid
        option.textContent = page.name; // Texto del option es el nombre

        // Agregar opción al select
        selectCombo.appendChild(option);
    });

    if (uid){
        selectCombo.value = uid;
    }else{
        selectCombo.value = "";
    }

}
