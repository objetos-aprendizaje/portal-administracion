import {
    controlsPagination,
    tabulatorBaseConfig,
    updatePaginationInfo,
} from "../tabulator_handler.js";
import { showModalConfirmation } from "../modal_handler";
import { TabulatorFull as Tabulator } from "tabulator-tables";
import { showToast } from "../toast.js";
import { apiFetch } from "../app.js";

let table;
const endPointTable = "/administration/suggestions_improvements/get_emails";
let selectedEmails = [];
document.addEventListener("DOMContentLoaded", async function () {
    initHandlers();
    initializeEmailsSuggestionsTable();

});

function initHandlers() {
    document
        .getElementById("btn-delete-emails")
        .addEventListener("click", function () {
            if (selectedEmails.length === 0) {
                showToast("No hay emails seleccionados", "error");
                return;
            }

            showModalConfirmation(
                "Eliminar emails",
                "¿Está seguro que desea eliminar los emails seleccionados?",
                "delete_emails"
            ).then((resultado) => {
                if (resultado) {
                    deleteBulkEmails();
                }
            });
        });

    document
        .getElementById("add-email-btn")
        .addEventListener("click", addEmail);

    document
        .getElementById("btn-update-table")
        .addEventListener("click", function () {
            table.setData(endPointTable);
        });
}

async function initializeEmailsSuggestionsTable() {
    const columns = [
        {
            title: "",
            field: "select",
            formatter: function (cell, formatterParams, onRendered) {
                const uid = cell.getRow().getData().uid;
                return `<input type="checkbox" data-uid="${uid}"/>`;
            },
            cssClass: "checkbox-cell",
            cellClick: function (e, cell) {
                // Lógica cuando se hace clic en la celda
                const checkbox = e.target;
                const uid = checkbox.getAttribute("data-uid");

                if (checkbox.checked) {
                    if (!selectedEmails.includes(uid)) selectedEmails.push(uid);
                } else {
                    // Lógica para quitar uid si el checkbox está desmarcado
                    const index = selectedEmails.indexOf(uid);
                    if (index > -1) {
                        selectedEmails.splice(index, 1);
                    }
                }
            },
            headerSort: false,
            width: 60,
        },
        { title: "Emails", field: "email" },
    ];

    table = new Tabulator("#list-emails", {
        ajaxURL: endPointTable,
        ...tabulatorBaseConfig,
        ajaxResponse: function (url, params, response) {
            updatePaginationInfo(table, response, "list-emails");

            selectedEmails = [];

            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });

    controlsPagination(table, "list-emails");
}

async function deleteBulkEmails() {
    const params = {
        url: "/administration/suggestions_improvements/delete_emails",
        method: "POST",
        body: { uidsEmails: selectedEmails },
        stringify: true,
        loader: true,
        toast: true,
    };

    apiFetch(params).then(() => {
        updateTable();
    });
}

/**
 * Envía el email introducido
 **/
function addEmail() {
    const email = document.getElementById("email-input").value.trim();

    // Validación de correo electrónico con regex
    const regex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

    if (!regex.test(email)) {
        showToast("Email inválido", "error");
        return;
    }
    const params = {
        url: "/administration/suggestions_improvements/save_email",
        method: "POST",
        body: { email: email },
        stringify: true,
        loader: true,
        toast: true,
    };

    apiFetch(params).then(() => {
        table.setData(endPointTable);
        document.getElementById("email-input").value = "";
    });
}
