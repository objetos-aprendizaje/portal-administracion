import {
    showModal,
} from "../modal_handler.js";
import {
    controlsPagination,
    tabulatorBaseConfig,
    updatePaginationInfo,
    controlsSearch,
    formatDateTime,
} from "../tabulator_handler.js";
import { heroicon } from "../heroicons.js";
import { TabulatorFull as Tabulator } from "tabulator-tables";

const endPointTable = "/learning_objects/educational_resources_per_users/get_list_users";

let EducationalResourcesPerUsersTable;
let EducationalResourcesPerUserTable;

let selectedEducationalResourcesPerUsers = [];

document.addEventListener("DOMContentLoaded", function () {
    initializeEducationalResourcesPerUsersTable();
    controlsSearch(EducationalResourcesPerUsersTable, endPointTable, "resources-per-users-table");
    controlsPagination(EducationalResourcesPerUsersTable, "resources-per-users-table");
});

function initializeEducationalResourcesPerUsersTable() {
    const columns = [
        { title: "Nombre", field: "first_name"},
        { title: "Apellidos", field: "last_name"},
        {
            title: "",
            field: "actions",
            formatter: function (cell, formatterParams, onRendered) {
                return `
                    <button type="button" class='btn action-btn' title='Ver'>${heroicon(
                        "eye",
                        "outline"
                    )}</button>
                `;
            },
            cellClick: function (e, cell) {
                e.preventDefault();
                const EducationalResourcesPerUsersClicked = cell.getRow().getData();
                fillFormEducationalResourcesPerUsersModal(EducationalResourcesPerUsersClicked.uid,EducationalResourcesPerUsersClicked.first_name,EducationalResourcesPerUsersClicked.last_name);
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
    ];

    EducationalResourcesPerUsersTable = new Tabulator("#resources-per-users-table", {
        ajaxURL: endPointTable,
        ...tabulatorBaseConfig,
        ajaxResponse: function (url, params, response) {
             updatePaginationInfo(
                EducationalResourcesPerUsersTable,
                response,
                "resources-per-users-table"
            );

            selectedEducationalResourcesPerUsers = [];

            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });
}

async function fillFormEducationalResourcesPerUsersModal(uid,last_name,first_name) {

    if (first_name == null){
        first_name  = "";
    }
    if (last_name == null){
        last_name  = "";
    }

    showModal("educational-resources-per-user-modal", `Recursos educativos consultados por ${first_name} ${last_name}`);

    const columns = [
        {
            title: "Fecha Consulta",
            field: "pivot.date",
            widthGrow:1,
            formatter: function (cell, formatterParams, onRendered) {
                const isoDate = cell.getValue();
                if (!isoDate) return "";
                return formatDateTime(isoDate);
            },
        },
        { title: "Título", field: "title", withGrow: 3 },
        { title: "Descripción", field: "description", withGrow: 3}
    ];

    const endPointTable = "/learning_objects/educational_resources_per_users/get_notifications/"+uid;

    EducationalResourcesPerUserTable = new Tabulator("#educational-resources-per-user-table", {
        ajaxURL: endPointTable,
        ...tabulatorBaseConfig,
        ajaxResponse: function (url, params, response) {
             updatePaginationInfo(
                EducationalResourcesPerUserTable,
                response,
                "educational-resources-per-user-table"
            );

            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });
    controlsPagination(EducationalResourcesPerUserTable, "educational-resources-per-user-table");
    controlsSearch(EducationalResourcesPerUserTable, endPointTable, "educational-resources-per-user-table");
}
