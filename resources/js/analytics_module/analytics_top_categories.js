import { TabulatorFull as Tabulator } from "tabulator-tables";
import {
    tabulatorBaseConfig,
    controlsPagination,
    controlsSearch,
    updatePaginationInfo,
} from "../tabulator_handler";
import { showModal, hideModal } from "../modal_handler.js";
import {
    apiFetch,
    getMultipleTomSelectInstance,
    getOptionsSelectedTomSelectInstance,
    getCsrfToken,
    getFlatpickrDateRangeSql,
    getFlatpickrDateRange,
    instanceFlatpickrNoHour,
} from "../app.js";
import * as d3 from "d3";
import * as XLSX from "xlsx";
import { heroicon } from "../heroicons.js";

window.XLSX = XLSX;

const endPointTable = "/analytics/users/get_top_table";

let analyticsTopTable;
let tomSelectFilterAcceptanceStatus;
let tomSelectFilterStatus;
let flatpickrCreatedDate;
let filters = [];

document.addEventListener("DOMContentLoaded", function () {
    drawTable();
    drawGraph();
    initializeTomSelect();

    document
        .getElementById("download-xlsx")
        .addEventListener("click", function () {
            analyticsTopTable.download("xlsx", "categorias.xlsx", {
                sheetName: "Recursos",
            });
        });

    document
        .getElementById("filter-top-categories-btn")
        .addEventListener("click", function () {
            showModal("filter-top-categories-modal");
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

    flatpickrCreatedDate = instanceFlatpickrNoHour("filter_created_date");
});

function drawTable() {
    const columns = [
        { title: "Título", field: "name", widthGrow: 8 },
        {
            title: "Número de estudiantes",
            field: "student_count",
            widthGrow: 2,
        },
    ];

    const { ...tabulatorBaseConfigOverrided } = tabulatorBaseConfig;

    if (analyticsTopTable) analyticsTopTable.destroy();
    analyticsTopTable = new Tabulator("#analytics-top", {
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
        ajaxResponse: async function (url, params, response) {
            updatePaginationInfo(analyticsTopTable, response, "analytics-top");
            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });

    controlsSearch(analyticsTopTable, endPointTable, "analytics-top");
    controlsPagination(analyticsTopTable, "analytics-top");
}

function drawGraph() {
    const params = {
        url: "/analytics/users/get_top_graph",
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": getCsrfToken(),
        },
        body: {
            filters,
        },
        stringify: true,
    };
    apiFetch(params).then((data) => {
        graficar(data);
    });
}

/**
 * Maneja el evento de clic en el botón para aplicar los filtros.
 * Recoge los filtros del modal, los muestra en la interfaz y vuelve a inicializar
 * la tabla de cursos con los nuevos filtros aplicados.
 */
function controlSaveHandlerFilters() {
    filters = collectFilters();

    drawTable();
    drawGraph();

    showFilters();
    hideModal("filter-top-categories-modal");
}

/**
 * Muestra los filtros aplicados en la interfaz de usuario.
 * Recorre el array de 'filters' y genera el HTML para cada filtro,
 * permitiendo su visualización y posterior eliminación. Además muestra u oculta
 * el botón de eliminación de filtros
 */
function showFilters() {
    // Eliminamos todos los filtros
    const currentFilters = document.querySelectorAll(".filter");

    // Recorre cada elemento y lo elimina
    currentFilters.forEach(function (filter) {
        filter.remove();
    });

    filters.forEach((filter) => {
        // Crea un nuevo div
        const newDiv = document.createElement("div");

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
        )}</button>`;

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

function resetFilters() {
    filters = [];

    tomSelectFilterAcceptanceStatus.clear();
    tomSelectFilterStatus.clear();
    flatpickrCreatedDate.clear();

    showFilters();

    drawTable();
    drawGraph();
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

    if (filterKey == "acceptance_status")
        tomSelectFilterAcceptanceStatus.clear();
    else if (filterKey == "status") tomSelectFilterStatus.clear();
    else if (filterKey == "created_at") flatpickrCreatedDate.clear();
    else document.getElementById(filterKey).value = "";

    showFilters();

    drawTable();
    drawGraph();
}

function graficar(datas) {
    const data = datas.map((element, index) => ({
        category: element.name,
        value: parseInt(element.student_count),
    }));

    const itemWithMaxAccesses = data.reduce((max, current) =>
        current.value > max.value ? current : max,
        { value: 0 }  // Valor inicial
    );

    // Array de colores
    const colors = [
        "#507ab9",
        "#2C4C7E",
        "#7E5E2C",
        "#7E2C4C",
        "#2C7E5E",
        "#B39264",
    ];

    // set the dimensions and margins of the graph
    const margin = { top: 30, right: 30, bottom: 120, left: 60 },
        width = 500 - margin.left - margin.right, // Fijo en 500px de ancho
        height = 500 - margin.top - margin.bottom; // Fijo en 500px de alto

    // Limpia el contenedor antes de crear el gráfico (para evitar duplicados)
    d3.select("#d3_graph").html("");

    // append the svg object to the body of the page
    const svg = d3
        .select("#d3_graph")
        .append("svg")
        .attr("width", width + margin.left + margin.right) // Fijo en 500px
        .attr("height", height + margin.top + margin.bottom) // Fijo en 500px
        .attr("style", "margin: 0 auto")
        .append("g")
        .attr("transform", `translate(${margin.left},${margin.top})`);

    // Crea el tooltip
    const tooltip = d3
        .select("#d3_graph")
        .append("div")
        .style("opacity", 0)
        .style("position", "absolute")
        .style("background-color", "white")
        .style("border", "solid")
        .style("border-width", "1px")
        .style("border-radius", "5px")
        .style("padding", "10px")
        .style("pointer-events", "none");

    // Funciones para mostrar y mover el tooltip con el ratón
    const showTooltip = function (event, d) {
        tooltip
            .html(`Categoría: ${d.category}<br>Estudiantes: ${d.value}`)
            .style("opacity", 1)
            .style("left", event.pageX + 10 + "px")
            .style("top", event.pageY - 20 + "px");
    };

    const moveTooltip = function (event, d) {
        tooltip
            .style("left", event.pageX + 10 + "px")
            .style("top", event.pageY - 20 + "px");
    };

    const hideTooltip = function (event, d) {
        tooltip.style("opacity", 0);
    };

    // X axis
    const x = d3
        .scaleBand()
        .range([0, width])
        .domain(data.map((d) => d.category))
        .padding(0.2);

    svg.append("g")
        .attr("transform", `translate(0, ${height})`)
        .call(d3.axisBottom(x))
        .selectAll("text")
        .attr("transform", "translate(-10,0)rotate(-45)")
        .style("text-anchor", "end")
        .style("font-size", "12px"); // Ajusta el tamaño del texto en el eje X

    // Y axis
    const y = d3
        .scaleLinear()
        .domain([
            0,
            itemWithMaxAccesses.value > 0 ? itemWithMaxAccesses.value : 1,
        ])
        .range([height, 0]);

    svg.append("g")
        .call(d3.axisLeft(y))
        .selectAll("text")
        .style("font-size", "12px"); // Ajusta el tamaño del texto en el eje Y

    // Añadir líneas horizontales (gridlines) en el eje Y sin la línea superior
    const gridlines = d3
        .axisLeft(y)
        .tickSize(-width)
        .tickFormat("") // No mostrar texto en las líneas
        .ticks(5); // Reducir la cantidad de líneas

    const grid = svg
        .append("g")
        .attr("class", "grid")
        .call(gridlines)
        .selectAll("line")
        .attr("stroke", "#e0e0e0") // Color de las líneas
        .attr("stroke-dasharray", "2,2"); // Líneas punteadas

    // Eliminar la primera línea que corresponde al valor máximo
    grid.filter((d) => d === itemWithMaxAccesses.value).remove(); // Eliminar la línea superior

    // Animación y dibujo de las barras
    svg.selectAll("rect")
        .data(data)
        .join("rect")
        .attr("x", (d) => x(d.category))
        .attr("y", height) // Empieza desde la base (altura máxima)
        .attr("width", x.bandwidth())
        .attr("fill", (d, i) => colors[i % colors.length]) // Usa el color según el índice
        .on("mouseover", showTooltip) // Evento para mostrar el tooltip
        .on("mousemove", moveTooltip) // Mueve el tooltip con el ratón
        .on("mouseout", hideTooltip) // Evento para ocultar el tooltip
        .transition() // Animación
        .duration(1000) // Duración de la animación en milisegundos
        .attr("y", (d) => y(d.value)) // Animar la posición de las barras
        .attr("height", (d) => height - y(d.value)); // Animar la altura final
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

    if (tomSelectFilterAcceptanceStatus) {
        const acceptanteStatuses = tomSelectFilterAcceptanceStatus.getValue();

        const selectedAcceptanteStatusesLabel =
            getOptionsSelectedTomSelectInstance(
                tomSelectFilterAcceptanceStatus
            );

        if (acceptanteStatuses.length) {
            addFilter(
                "Estados de aceptación",
                tomSelectFilterAcceptanceStatus.getValue(),
                selectedAcceptanteStatusesLabel,
                "acceptance_status",
                "acceptance_status"
            );
        }
    }

    if (tomSelectFilterStatus) {
        const statuses = tomSelectFilterStatus.getValue();

        const selectedStatusesLabel = getOptionsSelectedTomSelectInstance(
            tomSelectFilterStatus
        );

        if (statuses.length) {
            addFilter(
                "Estados",
                tomSelectFilterStatus.getValue(),
                selectedStatusesLabel,
                "status",
                "status"
            );
        }
    }

    if (flatpickrCreatedDate.selectedDates.length) {
        addFilter(
            "Fecha de inscripción",
            getFlatpickrDateRangeSql(flatpickrCreatedDate, false),
            getFlatpickrDateRange(flatpickrCreatedDate, false),
            "created_at",
            "created_at"
        );
    }

    return selectedFilters;
}

/**
 * Inicializa los controles 'TomSelect' para diferentes selecciones como
 * etiquetas, profesores, categorías, etc.
 * Configura varios aspectos como la creación de opciones y la eliminación.
 */
function initializeTomSelect() {
    tomSelectFilterAcceptanceStatus = getMultipleTomSelectInstance(
        "#filter_acceptance_status"
    );

    tomSelectFilterStatus = getMultipleTomSelectInstance("#filter_status");
}
