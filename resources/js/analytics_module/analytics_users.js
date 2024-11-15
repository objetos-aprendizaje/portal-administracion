import { TabulatorFull as Tabulator } from "tabulator-tables";
import {
    tabulatorBaseConfig,
    controlsPagination,
    controlsSearch,
    updatePaginationInfo,
    formatDateTime,
} from "../tabulator_handler";
import {
    apiFetch,
    getFlatpickrDateRangeSql,
    instanceFlatpickrNoHour,
    getMultipleTomSelectInstance,
    getFlatpickrDateRange,
    getMultipleFreeTomSelectInstance,
    getOptionsSelectedTomSelectInstance,
} from "../app.js";
import { heroicon } from "../heroicons.js";
import { showModal, hideModal } from "../modal_handler";
import * as d3 from "d3";

const endPointTableStudents = "/analytics/users/get_students";

let analyticsStudentsTable;
let flatpickrDateFilter;
let user_uid;

let filter_date = "";
let filter_type = "";

let tomSelectRolesFilter;
let dateUsersFilterFlatpickr;

let filters = [];

document.addEventListener("DOMContentLoaded", function () {
    //drawTable();
    drawGraph();

    drawTableStudents();

    flatpickrDateFilter = instanceFlatpickrNoHour("filter_date_accesses");

    document
        .getElementById("filter-users-btn")
        .addEventListener("click", function () {
            showModal("filter-users-modal");
        });

    document
        .getElementById("filter-btn")
        .addEventListener("click", function () {
            controlSaveHandlerFilters();
        });

    dateUsersFilterFlatpickr = instanceFlatpickrNoHour("date_users");
    tomSelectRolesFilter = getMultipleFreeTomSelectInstance("#roles_filter");

    document
    .getElementById("delete-all-filters")
    .addEventListener("click", function () {
        resetFilters();
    });
});

function drawGraph() {
    const params = {
        url: "/analytics/users/get_user_roles_graph",
        method: "GET",
    };
    apiFetch(params).then((data) => {
        graficar(data);
    });
}

function graficar(datas) {
    // set the dimensions and margins of the graph
    const width = 700,
        height = 600,
        margin = 110;

    const colores = ["#2C4C7E", "#7E5E2C", "#2C7E5E", "#7E2C4C"];

    // The radius of the pieplot is half the width or half the height (smallest one). I subtract a bit of margin.
    const radius = Math.min(width, height) / 2 - margin;

    // append the svg object to the div called 'my_dataviz'
    const svg = d3
        .select("#d3_graph")
        .append("svg")
        .attr("width", width)
        .attr("height", height)
        .attr("style", `top: -70px; position: relative; margin:0 auto`)
        .append("g")
        .attr("transform", `translate(${width / 2},${height / 2})`);

    // Create dummy data
    const data = {
        Estudiante: datas[0]["users_count"],
        Administrador: datas[1]["users_count"],
        Docente: datas[2]["users_count"],
        Gestor: datas[3]["users_count"],
    };

    // set the color scale
    const color = d3
        .scaleOrdinal()
        .domain(["Estudiante", "Administrador", "Docente", "Gestor"])
        .range(d3.schemeDark2);

    // Compute the position of each group on the pie:
    const pie = d3
        .pie()
        .sort(null) // Do not sort group by size
        .value((d) => d[1]);
    const data_ready = pie(Object.entries(data));

    // The arc generator
    const arc = d3
        .arc()
        .innerRadius(radius * 0.5) // This is the size of the donut hole
        .outerRadius(radius * 0.8);

    // Another arc that won't be drawn. Just for labels positioning
    const outerArc = d3
        .arc()
        .innerRadius(radius * 0.9)
        .outerRadius(radius * 0.9);

    // Build the pie chart with animation
    svg.selectAll("allSlices")
        .data(data_ready)
        .join("path")
        .attr("d", arc)
        .attr("fill", function (d) {
            return colores[d.index];
        })
        .attr("stroke", "white")
        .style("stroke-width", "2px")
        .style("opacity", 0.7)
        .transition() // Add transition
        .duration(1000) // Duration of the transition
        .attrTween("d", function (d) {
            const interpolate = d3.interpolate(d.startAngle, d.endAngle);
            return function (t) {
                d.endAngle = interpolate(t);
                return arc(d);
            };
        });

    // Add the polylines between chart and labels:
    svg.selectAll("allPolylines")
        .data(data_ready)
        .join("polyline")
        .attr("stroke", "black")
        .style("fill", "none")
        .attr("stroke-width", 1)
        .attr("points", function (d) {
            const posA = arc.centroid(d); // line insertion in the slice
            const posB = outerArc.centroid(d); // line break: we use the other arc generator that has been built only for that
            const posC = outerArc.centroid(d); // Label position = almost the same as posB
            const midangle = d.startAngle + (d.endAngle - d.startAngle) / 2; // we need the angle to see if the X position will be at the extreme right or extreme left
            posC[0] = radius * 0.95 * (midangle < Math.PI ? 1 : -1); // multiply by 1 or -1 to put it on the right or the left
            return [posA, posB, posC];
        });

    // Add the labels with animation
    svg.selectAll("allLabels")
        .data(data_ready)
        .join("text")
        .text(function (d) {
            let sufijo = "s";
            const vocales = ["a", "e", "i", "o", "u"];
            const ultimaLetra = d.data[0][d.data[0].length - 1].toLowerCase();
            if (!vocales.includes(ultimaLetra)) {
                sufijo = "es";
            }
            if (d.value > 1 || d.value == 0) {
                return d.value + " " + d.data[0] + sufijo;
            }
            if (d.value == 1) {
                return d.value + " " + d.data[0];
            }
        })
        .attr("transform", function (d) {
            const pos = outerArc.centroid(d);
            const midangle = d.startAngle + (d.endAngle - d.startAngle) / 2;
            pos[0] = radius * 0.99 * (midangle < Math.PI ? 1 : -1);
            return `translate(${pos})`;
        })
        .style("text-anchor", function (d) {
            const midangle = d.startAngle + (d.endAngle - d.startAngle) / 2;
            return midangle < Math.PI ? "start" : "end";
        })
        .attr("opacity", 0) // Start with opacity 0
        .transition() // Add transition
        .duration(1000) // Duration of the transition
        .attr("opacity", 1); // Fade in the text
}

function drawTableStudents() {
    const columns = [
        { title: "Nombre", field: "first_name", widthGrow: 4 },
        { title: "Apellidos", field: "last_name", widthGrow: 8 },
        {
            title: "Email",
            field: "email",
            widthGrow: 5,
        },
        {
            title: "Último inicio de sesión",
            field: "last_login",
            widthGrow: 4,
            formatter: function (cell, formatterParams, onRendered) {
                return cell.getValue()
                    ? formatDateTime(cell.getValue())
                    : "No disponible";
            },
        },
        {
            title: "Nº de cursos inscritos",
            field: "count_courses",
            widthGrow: 3,
        },
        {
            title: "",
            field: "actions",
            formatter: function (cell, formatterParams, onRendered) {
                return `
                    <button type="button" class='btn action-btn'>${heroicon(
                        "eye",
                        "outline"
                    )}</button>
                `;
            },
            cellClick: function (e, cell) {
                e.preventDefault();
                const data = cell.getRow().getData();
                showModal("analytics-user-modal", "Datos del usuario");
                fillDataUserModal();
                user_uid = data.uid;
                sendData();
                filter_date = "";
                filter_type = "";
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
    ];

    const { ...tabulatorBaseConfigOverrided } = tabulatorBaseConfig;

    analyticsStudentsTable = new Tabulator("#analytics-students-table", {
        ajaxURL: endPointTableStudents,
        ...tabulatorBaseConfigOverrided,
        ajaxConfig: "POST",
        ajaxParams: {
            filters: {
                ...filters,
            },
        },
        ajaxResponse: async function (url, params, response) {
            updatePaginationInfo(
                analyticsStudentsTable,
                response,
                "analytics-students-table"
            );
            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });

    controlsSearch(
        analyticsStudentsTable,
        endPointTableStudents,
        "analytics-students-table"
    );

    controlsPagination(analyticsStudentsTable, "analytics-students-table");
}
function fillDataUserModal() {
    const filter_data_input = document.querySelector("#filter_date_accesses");
    const filter_type_input = document.querySelector("#filter_type");

    filter_data_input.addEventListener("change", function () {
        filter_date = getFlatpickrDateRangeSql(flatpickrDateFilter);
        if (filter_date.length > 1) {
            const startDate = new Date(filter_date[0]);
            const endDate = new Date(filter_date[1]);
            const diffInMs = endDate - startDate;
            const diffInDays = diffInMs / (1000 * 60 * 60 * 24);
            const days = Math.ceil(diffInDays);
            if (days > 45) {
                filter_type = "YYYY-MM";
                document.getElementById("filter_type").value = "YYYY-MM";
            } else if (days > 730) {
                filter_type = "YYYY";
                document.getElementById("filter_type").value = "YYYY";
            }

            sendData();
        }
    });

    filter_type_input.addEventListener("change", function () {
        filter_type = document.getElementById("filter_type").value;
        if (filter_type != null) {
            sendData();
        }
    });
}
function sendData() {
    const formData = new FormData();

    formData.append("filter_date", filter_date);
    formData.append("filter_type", filter_type);
    formData.append("user_uid", user_uid);

    const params = {
        url: "/analytics/users/get_students_data",
        method: "POST",
        body: formData,
        loader: true,
    };

    apiFetch(params).then((data) => {
        document.getElementById("last-login-date").innerHTML = data[
            "last_login"
        ]
            ? formatDateTime(data["last_login"])
            : "No disponible";
        document.getElementById("count-inscribed-courses").innerHTML =
            data["count_inscribed_courses"];
        drawGraphUser(data[0], "first_graph", data[5]);
        drawGraphUser(data[1], "second_graph", data[6]);
        drawGraphUser(data[2], "third_graph", data[7]);
        let date = data[3].split(",");
        flatpickrDateFilter.setDate([
            convertirFechas(date[0]),
            convertirFechas(date[1]),
        ]);
        document.getElementById("filter_type").value = data[4];
    });
}

/**
 * Maneja el evento de clic en el botón para aplicar los filtros.
 * Recoge los filtros del modal, los muestra en la interfaz y vuelve a inicializar
 * la tabla de cursos con los nuevos filtros aplicados.
 */
function controlSaveHandlerFilters() {
    filters = collectFilters();

    showFilters();
    hideModal("filter-users-modal");

    if (analyticsStudentsTable) analyticsStudentsTable.destroy();
    drawTableStudents();
}

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

    if (dateUsersFilterFlatpickr.selectedDates.length) {
        addFilter(
            "Fecha de creación",
            getFlatpickrDateRangeSql(dateUsersFilterFlatpickr),
            getFlatpickrDateRange(dateUsersFilterFlatpickr, false),
            "filter_creation_date",
            "creation_date"
        );
    }

    const rolesFilterSelected = tomSelectRolesFilter.getValue();
    if (rolesFilterSelected.length) {
        const selectedRolesLabel =
            getOptionsSelectedTomSelectInstance(tomSelectRolesFilter);

        addFilter(
            "Roles",
            rolesFilterSelected,
            selectedRolesLabel,
            "roles",
            "roles"
        );
    }

    return selectedFilters;
}

function drawGraphUser(datas, container_id, max) {
    // Limpiar el contenedor
    document.getElementById(container_id).innerHTML = "";

    const data = datas.map((element) => ({
        title: element.period,
        count: element.access_count,
    }));

    let itemWithMaxAccesses = max;

    if (itemWithMaxAccesses == 0 || itemWithMaxAccesses == null) {
        itemWithMaxAccesses = 1;
    }

    // Dimensiones del gráfico
    const margin = { top: 30, right: 30, bottom: 70, left: 60 },
        containerWidth = document.getElementById(container_id).clientWidth, // Ancho del contenedor
        width = containerWidth - margin.left - margin.right,
        height = 400 - margin.top - margin.bottom;

    // Crear el SVG dentro del contenedor
    const svg = d3
        .select("#" + container_id)
        .append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom)
        .append("g")
        .attr("transform", `translate(${margin.left},${margin.top})`);

    // Eje X
    const x = d3
        .scaleBand()
        .range([0, width])
        .domain(data.map((d) => d.title))
        .padding(0.2);
    svg.append("g")
        .attr("transform", `translate(0, ${height})`)
        .call(d3.axisBottom(x))
        .selectAll("text")
        .attr("transform", "translate(-10,0)rotate(-45)")
        .style("text-anchor", "end")
        .style("font-size", "12px"); // Tamaño de texto de 12px

    // Eje Y
    const y = d3
        .scaleLinear()
        .domain([0, itemWithMaxAccesses])
        .range([height, 0]);
    svg.append("g")
        .call(d3.axisLeft(y))
        .selectAll("text")
        .style("font-size", "12px"); // Tamaño de texto de 12px

    // Agregar líneas punteadas horizontales al eje Y
    svg.append("g")
        .selectAll("line")
        .data(y.ticks()) // Obtener los valores del eje Y
        .enter()
        .append("line")
        .attr("x1", 0)
        .attr("x2", width)
        .attr("y1", (d) => y(d))
        .attr("y2", (d) => y(d))
        .attr("stroke", "#ccc") // Color de las líneas
        .attr("stroke-dasharray", "2,2"); // Línea punteada

    // Dibujar las barras con animación
    svg.selectAll("mybar")
        .data(data)
        .join("rect")
        .attr("x", (d) => x(d.title))
        .attr("y", height) // Iniciar en la parte inferior
        .attr("width", x.bandwidth())
        .attr("height", 0) // Iniciar sin altura
        .attr("fill", "#2C4C7E") // Color fijo
        .transition() // Agregar transición
        .duration(800) // Duración de la animación
        .attr("y", (d) => y(d.count)) // Altura final
        .attr("height", (d) => height - y(d.count)); // Altura final
}

function resetFilters() {
    filters = [];
    showFilters();
    drawTableStudents();

    dateUsersFilterFlatpickr.clear();
    tomSelectRolesFilter.clear();
}

function controlDeleteFilters(deleteBtn) {
    const filterKey = deleteBtn.getAttribute("data-filter-key");

    let removedFilters = filters.filter(
        (filter) => filter.filterKey === filterKey
    );

    removedFilters.forEach((removedFilter) => {
        if (removedFilter.filterKey === "roles") {
            tomSelectRolesFilter.clear();
        } else if (removedFilter.filterKey === "filter_creation_date") {
            dateUsersFilterFlatpickr.clear();
        }
    });

    filters = filters.filter((filter) => filter.filterKey !== filterKey);

    showFilters();
    drawTableStudents();
}

function convertirFechas(date) {
    const regex = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/;
    const fecha = new Date(date);
    const dia = String(fecha.getDate()).padStart(2, "0"); // Obtener día con dos dígitos
    const mes = String(fecha.getMonth() + 1).padStart(2, "0"); // Obtener mes (0-11) + 1
    const anio = fecha.getFullYear(); // Obtener año
    const fechaFormateada = `${dia}-${mes}-${anio}`;
    return fechaFormateada;
}
