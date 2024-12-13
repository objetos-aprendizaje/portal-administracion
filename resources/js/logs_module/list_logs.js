import {
    hideModal,
    showModal
} from "../modal_handler.js";
import {
    formatDateTime,
    getMultipleTomSelectInstance,
    getLiveSearchTomSelectInstance,
    instanceFlatpickr,
    getOptionsSelectedTomSelectInstance,
    getFlatpickrDateRangeSql,
    getFlatpickrDateRange,
 } from "../app.js";
import {
    controlsPagination,
    tabulatorBaseConfig,
    updatePaginationInfo,
    controlsSearch,
} from "../tabulator_handler.js";
import { TabulatorFull as Tabulator } from "tabulator-tables";
import { heroicon } from "../heroicons.js";

let logsTable;
let tomSelectEntitiesFilter;
let tomSelectUsersFilter;
let flatpickrDate;
let filters = [];
const endPointTable = "/logs/list_logs/get_logs";

document.addEventListener("DOMContentLoaded", () => {
    initializeLogsTable();


    controlReloadTable();

    initHandlers();
});

function initHandlers() {
    document
        .getElementById("btn-reload-table")
        .addEventListener("click", function () {
            logsTable.setData(endPointTable);
        });

    document
        .getElementById("filter-logs-btn")
        .addEventListener("click", function () {
            showModal("filter-logs-modal");
        });
    document
        .getElementById("filter-btn")
        .addEventListener("click", function () {
            controlSaveHandlerFilters();
        });

    document
        .getElementById("delete-all-filters")
        .addEventListener("click", function () {
            resetFilters();
        });

    tomSelectEntitiesFilter =
        getMultipleTomSelectInstance("#filter_entities");

    tomSelectUsersFilter = getLiveSearchTomSelectInstance(
        "#filter_users",
        "/users/list_users/search_users_backend/",
        function (entry) {
            return {
                value: entry.uid,
                text: `${entry.first_name} ${entry.last_name}`,
            };
        }
    );
    flatpickrDate = instanceFlatpickr("filter_date");
}

/**
 * Configuración de la tabla de llamadas utilizando la biblioteca Tabulator.
 * Define las columnas, obtiene los datos del endpoint y maneja la paginación.
 */
function initializeLogsTable() {
    const columns = [
        { title: "Entidad", field: "entity" },
        { title: "Información", field: "info" },
        { title: "Nombre", field: "user_first_name" },
        { title: "Apellidos", field: "user_last_name" },
        { title: "Fecha", field: "created_at" },
    ];

    logsTable = new Tabulator("#logs-table", {
        ajaxURL: endPointTable,
        ...tabulatorBaseConfig,
        ajaxParams: {
            filters: {
                ...filters,
            },
        },
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

    controlsPagination(logsTable, "logs-table");
    controlsSearch(logsTable, endPointTable, "logs-table");
}
/**
 * Maneja el evento de clic en el botón para aplicar los filtros.
 * Recoge los filtros del modal, los muestra en la interfaz y vuelve a inicializar
 * la tabla de cursos con los nuevos filtros aplicados.
 */
function controlSaveHandlerFilters() {
    filters = collectFilters();

    showFilters();
    hideModal("filter-logs-modal");

    initializeLogsTable();
}
/**
 * Muestra los filtros aplicados en la interfaz de usuario.
 * Recorre el array de 'filters' y genera el HTML para cada filtro,
 * permitiendo su visualización y posterior eliminación. Además muestra u oculta
 * el botón de eliminación de filtros
 */
function showFilters() {
    // Eliminamos todos los filtros
    var currentFilters = document.querySelectorAll(".filter");

    // Recorre cada elemento y lo elimina
    currentFilters.forEach(function (filter) {
        filter.remove();
    });

    filters.forEach((filter) => {
        // Crea un nuevo div
        var newDiv = document.createElement("div");

        // Agrega la clase 'filter' al div
        newDiv.classList.add("filter");

        // Establece el HTML del nuevo div
        newDiv.innerHTML = `
            <div>${filter.name}: ${filter.option}</div>
            <button data-filter-key="${
                filter.filterKey
            }" class="delete-filter-btn">${heroicon(
            "x-mark",
            "outline"
        )}</button>
        `;

        // Agrega el nuevo div al div existente
        document.getElementById("filters").prepend(newDiv);
    });

    const deleteAllFiltersBtn = document.getElementById("delete-all-filters");

    if (filters.length == 0) deleteAllFiltersBtn.classList.add("hidden");
    else deleteAllFiltersBtn.classList.remove("hidden");

    // Agregamos los listeners de eliminación a los filtros
    document.querySelectorAll(".delete-filter-btn").forEach((deleteFilter) => {
        deleteFilter.addEventListener("click", (event) => {
            controlDeleteFilters(event.currentTarget);
        });
    });
}

/**
 * Recoge todos los filtros aplicados en el modal de filtros.
 * Obtiene los valores de los elementos de entrada y los añade al array
 * de filtros seleccionados.
 */
function collectFilters() {
    let selectedFilters = [];
    /**
     *
     * @param {*} name nombre del filtro
     * @param {*} value Valor correspondiente a la opción seleccionada
     * @param {*} option Opción seleccionada
     * @param {*} filterKey Id correspondiente al filtro y al campo input al que corresponde
     * @param {*} database_field Nombre del campo de la BD correspondiente al filtro
     * @param {*} filterType Tipo de filtro
     *
     * Añade filtros al array
     */
    function addFilter(name, value, option, filterKey, database_field = "") {
        if (value && value !== "") {
            selectedFilters.push({
                name,
                value,
                option,
                filterKey,
                database_field,
            });
        }
    }
        // Collect values from TomSelects
    if (tomSelectEntitiesFilter) {
        const entities = tomSelectEntitiesFilter.getValue();

        const selectEdentitiesLabel = getOptionsSelectedTomSelectInstance(
            tomSelectEntitiesFilter
        );

        if (entities.length)
            addFilter(
                "Entidades",
                entities,
                selectEdentitiesLabel,
                "filter_entities",
                "entity"
            );
    }
    if (tomSelectUsersFilter) {
        const users = tomSelectUsersFilter.getValue();

        const selectUsersLabel = getOptionsSelectedTomSelectInstance(
            tomSelectUsersFilter
        );

        if (users.length)
            addFilter(
                "Usuarios",
                users,
                selectUsersLabel,
                "filter_users",
                "users"
            );
    }

    if (flatpickrDate.selectedDates.length)
        addFilter(
            "Fecha",
            getFlatpickrDateRangeSql(flatpickrDate),
            getFlatpickrDateRange(flatpickrDate),
            "filter_date",
            "date"
        );

    return selectedFilters;
}
/**
 * Maneja el evento de clic para eliminar un filtro específico.
 * Cuando se hace clic en un botón con la clase 'delete-filter-btn',
 * este elimina el filtro correspondiente del array 'filters' y actualiza
 * la visualización y la tabla de cursos.
 */
function controlDeleteFilters(deleteBtn) {
    const filterKey = deleteBtn.getAttribute("data-filter-key");

    filters = filters.filter((filter) => filter.filterKey !== filterKey);

    if (filterKey == "filter_entities") tomSelectEntitiesFilter.clear();
    if (filterKey == "filter_users") tomSelectUsersFilter.clear();
    if (filterKey == "filter_date") flatpickrDate.clear();
    else document.getElementById(filterKey).value = "";

    showFilters();
    initializeLogsTable();
}
function resetFilters() {
    filters = [];
    showFilters();
    initializeLogsTable();

    tomSelectEntitiesFilter.clear();
    tomSelectUsersFilter.clear();
    flatpickrDate.clear();

}
/**
 * Este bloque escucha el evento de clic en el botón de recargar tabla
 * y actualiza los datos de la tabla.
 *
 */
function controlReloadTable() {
    const reloadTableBtn = document.getElementById("btn-reload-table");
    reloadTableBtn.addEventListener("click", function () {
        logsTable.setData(endPointTable);
    });
}
