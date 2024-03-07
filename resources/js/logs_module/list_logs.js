import { formatDateTime } from "../app.js";
import {
    controlsPagination,
    tabulatorBaseConfig,
    updatePaginationInfo,
    controlsSearch,
} from "../tabulator_handler.js";
import { TabulatorFull as Tabulator } from "tabulator-tables";

let logsTable;
const endPointTable = "/logs/list_logs/get_logs";

document.addEventListener("DOMContentLoaded", () => {
    initializeLogsTable();
    controlsPagination(logsTable, "logs-table");
    controlReloadTable();
    controlsSearch(logsTable, endPointTable, "logs-table");

    initHandlers();
});

function initHandlers() {
    document
        .getElementById("btn-reload-table")
        .addEventListener("click", function () {
            logsTable.replaceData(endPointTable);
        });
}

/**
 * Configuración de la tabla de llamadas utilizando la biblioteca Tabulator.
 * Define las columnas, obtiene los datos del endpoint y maneja la paginación.
 */
function initializeLogsTable() {
    const columns = [
        { title: "Entidad", field: "entity" },
        { title: "Tipo", field: "type" },
        { title: "Nombre", field: "user_first_name" },
        { title: "Apellidos", field: "user_last_name" },
        { title: "Fecha", field: "created_at" },
    ];

    logsTable = new Tabulator("#logs-table", {
        ajaxURL: endPointTable,
        ...tabulatorBaseConfig,
        ajaxResponse: function (url, params, response) {
            updatePaginationInfo(logsTable, response, "logs-table");

            return {
                last_page: response.last_page,
                data: response.data.map((log) => {
                    return {
                        ...log,
                        created_at: formatDateTime(log.created_at),
                    };
                }),
            };
        },
        columns: columns,
    });
}
