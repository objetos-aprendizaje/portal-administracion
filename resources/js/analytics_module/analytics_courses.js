import {
    tabulatorBaseConfig,
    controlsPagination,
    controlsSearch,
    updatePaginationInfo,
    formatDateTime,
} from "../tabulator_handler";
import { TabulatorFull as Tabulator } from "tabulator-tables";
import {
    apiFetch,
    getFlatpickrDateRangeSql,
    getFlatpickrDateRange,
    instanceFlatpickrNoHour,
    getMultipleTomSelectInstance,
    getLiveSearchTomSelectInstance,
    instanceFlatpickr,
    getOptionsSelectedTomSelectInstance,
    getCsrfToken,
} from "../app.js";
import { heroicon } from "../heroicons.js";
import * as d3 from "d3";
import * as XLSX from "xlsx";
import { showModal, hideModal } from "../modal_handler";

window.XLSX = XLSX;
const endPointTable = "/analytics/courses/get";

let analyticsPoaTable;
let courseUid;
let filterDate = "";
let filterType = "";
let flatpickrDateFilter;

let tomSelectCategoriesFilter;
let tomSelectLearningResultsFilter;

let tomSelectNoCoordinatorsTeachersFilter;
let tomSelectCoordinatorsTeachersFilter;

let tomSelectCreatorsFilter;
let tomSelectCourseStatusesFilter;
let tomSelectCallsFilter;
let tomSelectCourseTypesFilter;

let flatpickrInscriptionDate;
let flatpickrRealizationDate;

let selectedCompetencesFilter = [];

let filters = [];

let graphData = [];

document.addEventListener("DOMContentLoaded", function () {
    flatpickrDateFilter = instanceFlatpickrNoHour("filter_date_accesses");

    document
        .getElementById("itemsPerGraph")
        .addEventListener("change", changePaginationSizeGraph);

    document
        .getElementById("download-xlsx-course")
        .addEventListener("click", function () {
            analyticsPoaTable.download("xlsx", "cursos.xlsx", {
                sheetName: "Cursos",
            });
        });

    document
        .getElementById("filter-courses-btn")
        .addEventListener("click", function () {
            showModal("filter-courses-modal");
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

    initializeTomSelect();
    initializeFlatpickrDates();

    drawTable();
    drawGraph();

    drawGraphStatusesCourses();
});

/**
 * Inicializa los controles de fecha 'flatpickr' para los filtros de fecha de inicio
 * y fecha de fin, configurando el formato y el idioma en español.
 */
function initializeFlatpickrDates() {
    flatpickrInscriptionDate = instanceFlatpickr("filter_inscription_date");
    flatpickrRealizationDate = instanceFlatpickr("filter_realization_date");
}

function resetFilters() {
    filters = [];
    showFilters();

    tomSelectCallsFilter.clear();
    tomSelectCourseStatusesFilter.clear();
    tomSelectCourseTypesFilter.clear();
    tomSelectCreatorsFilter.clear();
    flatpickrInscriptionDate.clear();
    flatpickrRealizationDate.clear();
    tomSelectNoCoordinatorsTeachersFilter.clear();
    tomSelectLearningResultsFilter.clear();
    tomSelectCoordinatorsTeachersFilter.clear();
    tomSelectCategoriesFilter.clear();

    document.getElementById("filter_min_ects_workload").value = "";
    document.getElementById("filter_max_ects_workload").value = "";
    document.getElementById("filter_min_cost").value = "";
    document.getElementById("filter_max_cost").value = "";
    document.getElementById("filter_min_required_students").value = "";
    document.getElementById("filter_max_required_students").value = "";
    document.getElementById("filter_center").value = "";
    document.getElementById("filter_validate_student_registrations").value = "";
    document.getElementById("filter_embeddings").value = "";

    drawTable();
    drawGraph();
}

function drawTable() {
    const columns = [
        { title: "Título", field: "title", widthGrow: 8 },
        {
            title: "Número de visitas",
            field: "visits_count",
            widthGrow: 2,
        },
        {
            title: "Número de accesos",
            field: "accesses_count",
            widthGrow: 2,
        },
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
                const data = cell.getRow().getData();
                showModal("analytics-course-modal", "Datos del curso");
                fillDataCourseModal();
                courseUid = data.uid;
                sendData();
                document.getElementById("last_access").innerHTML = "";
                document.getElementById("unique_users").innerHTML = "";
                document.getElementById("insribed_users").innerHTML = "";
                document.getElementById("last_visit").innerHTML = "";
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
    ];

    const { ...tabulatorBaseConfigOverrided } = tabulatorBaseConfig;

    if (analyticsPoaTable) analyticsPoaTable.destroy();
    analyticsPoaTable = new Tabulator("#analytics-poa", {
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
            updatePaginationInfo(analyticsPoaTable, response, "analytics-poa");
            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });

    controlsSearch(analyticsPoaTable, endPointTable, "analytics-poa");
    controlsPagination(analyticsPoaTable, "analytics-poa");
}

function drawGraphStatusesCourses() {
    const params = {
        url: "/analytics/courses/get_courses_statuses_graph",
        method: "GET",
        loader: true,
    };

    apiFetch(params).then((data) => {
        const dataGraph = data.map((item) => ({
            title: item.name,
            count: item.courses_count,
        }));

        graficar(
            dataGraph,
            "all",
            "d3_graph_courses",
            "d3_graph_courses_x_axis"
        );
    });
}

function drawGraph() {
    const params = {
        url: "/analytics/courses/get_poa_graph",
        method: "POST",
        loader: true,
        body: {
            filters: filters,
        },
        stringify: true,
    };

    apiFetch(params).then((data) => {
        const quantity = document.getElementById("itemsPerGraph").value;

        graphData = JSON.parse(JSON.stringify(data));

        // Acortar la lista de datos si se ha seleccionado una cantidad
        if (quantity !== "all") data.splice(quantity);

        graficar(data, quantity, "d3_graph", "d3_graph_x_axis");
        graficarTreeMap(graphData);
    });
}

function changePaginationSizeGraph() {
    const quantity = document.getElementById("itemsPerGraph").value;

    let dataSliced = [];
    if (quantity == "all") {
        dataSliced = graphData;
    } else {
        dataSliced = graphData.filter((_, index) => index < quantity);
    }

    graficar(dataSliced, quantity, "d3_graph", "d3_graph_x_axis");
}

function fillDataCourseModal() {
    const filterDataInput = document.querySelector("#filter_date_accesses");
    const filterTypeInput = document.querySelector("#filter_type");

    filterDataInput.addEventListener("change", function () {
        filterDate = getFlatpickrDateRangeSql(flatpickrDateFilter);
        if (filterDate.length > 1) {
            const startDate = new Date(filterDate[0]);
            const endDate = new Date(filterDate[1]);
            const diffInMs = endDate - startDate;
            const diffInDays = diffInMs / (1000 * 60 * 60 * 24);
            const days = Math.ceil(diffInDays);
            if (days > 20) {
                filterType = "YYYY-MM";
                document.getElementById("filter_type").value = "YYYY-MM";
            } else if (days > 365) {
                filterType = "YYYY";
                document.getElementById("filter_type").value = "YYYY";
            }

            sendData();
        }
    });

    filterTypeInput.addEventListener("change", function () {
        const filterType = document.getElementById("filter_type").value;
        if (filterType != null) {
            sendData();
        }
    });
}

/**
 * Inicializa los controles 'TomSelect' para diferentes selecciones como
 * etiquetas, profesores, categorías, etc.
 * Configura varios aspectos como la creación de opciones y la eliminación.
 */
function initializeTomSelect() {
    tomSelectCategoriesFilter =
        getMultipleTomSelectInstance("#filter_categories");

    tomSelectCoordinatorsTeachersFilter = getMultipleTomSelectInstance(
        "#filter_coordinators_teachers"
    );
    tomSelectNoCoordinatorsTeachersFilter = getMultipleTomSelectInstance(
        "#filter_no_coordinators_teachers"
    );

    tomSelectLearningResultsFilter = getLiveSearchTomSelectInstance(
        "#filter_learning_results",
        "/searcher/get_learning_results/",
        function (entry) {
            return {
                value: entry.uid,
                text: entry.name,
            };
        }
    );

    tomSelectCourseStatusesFilter = getMultipleTomSelectInstance(
        "#filter_courses_statuses"
    );

    tomSelectCallsFilter = getMultipleTomSelectInstance("#filter_calls");

    tomSelectCourseTypesFilter = getMultipleTomSelectInstance(
        "#filter_course_types"
    );

    tomSelectCreatorsFilter = getLiveSearchTomSelectInstance(
        "#filter_creators",
        "/users/list_users/search_users_backend/",
        function (entry) {
            return {
                value: entry.uid,
                text: `${entry.first_name} ${entry.last_name}`,
            };
        }
    );
}

/**
 * Maneja el evento de clic en el botón para aplicar los filtros.
 * Recoge los filtros del modal, los muestra en la interfaz y vuelve a inicializar
 * la tabla de cursos con los nuevos filtros aplicados.
 */
function controlSaveHandlerFilters() {
    filters = collectFiltersCourses();

    showFilters();
    hideModal("filter-courses-modal");

    drawTable();
    drawGraph();
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

function collectFiltersCourses() {
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

    if (flatpickrInscriptionDate.selectedDates.length)
        addFilter(
            "Fecha de inscripción",
            getFlatpickrDateRangeSql(flatpickrInscriptionDate),
            getFlatpickrDateRange(flatpickrInscriptionDate),
            "filter_inscription_date",
            "inscription_date"
        );

    if (flatpickrRealizationDate.selectedDates.length)
        addFilter(
            "Fecha de realización",
            getFlatpickrDateRangeSql(flatpickrRealizationDate),
            getFlatpickrDateRange(flatpickrRealizationDate),
            "filter_realization_date",
            "realization_date"
        );

    let selectElementFilterEmbeddings =
        document.getElementById("filter_embeddings");
    if (selectElementFilterEmbeddings.value) {
        addFilter(
            "¿Tiene embeddings?",
            selectElementFilterEmbeddings.value,
            selectElementFilterEmbeddings.value == "1" ? "Sí" : "No",
            "filter_embeddings",
            "embeddings"
        );
    }

    let selectElementValidateStudents = document.getElementById(
        "filter_validate_student_registrations"
    );
    if (selectElementValidateStudents.value) {
        addFilter(
            "Validar registro de estudiantes",
            selectElementValidateStudents.value,
            selectElementValidateStudents.value == "1" ? "Sí" : "No",
            "filter_validate_student_registrations",
            "validate_student_registrations"
        );
    }

    const filterMinEctsWorkload = document.getElementById(
        "filter_min_ects_workload"
    ).value;

    if (filterMinEctsWorkload) {
        addFilter(
            "Mínimo ECTS",
            filterMinEctsWorkload,
            filterMinEctsWorkload,
            "filter_min_ects_workload",
            "min_ects_workload"
        );
    }

    const filterMaxEctsWorkload = document.getElementById(
        "filter_max_ects_workload"
    ).value;

    if (filterMaxEctsWorkload) {
        addFilter(
            "Máximo ECTS",
            filterMaxEctsWorkload,
            filterMaxEctsWorkload,
            "filter_max_ects_workload",
            "max_ects_workload"
        );
    }

    const filterMinCost = document.getElementById("filter_min_cost").value;
    if (filterMinCost)
        addFilter(
            "Coste mínimo",
            filterMinCost,
            filterMinCost,
            "filter_min_cost",
            "min_cost"
        );

    const filterMaxCost = document.getElementById("filter_max_cost").value;
    if (filterMaxCost)
        addFilter(
            "Coste máximo",
            filterMaxCost,
            filterMaxCost,
            "filter_max_cost",
            "max_cost"
        );

    // Collect values from TomSelects
    if (tomSelectCategoriesFilter) {
        const categories = tomSelectCategoriesFilter.getValue();

        const selectedCategoriesLabel = getOptionsSelectedTomSelectInstance(
            tomSelectCategoriesFilter
        );

        if (categories.length)
            addFilter(
                "Categorías",
                categories,
                selectedCategoriesLabel,
                "Categories",
                "categories"
            );
    }

    if (tomSelectCoordinatorsTeachersFilter) {
        const teachersCoordinators =
            tomSelectCoordinatorsTeachersFilter.getValue();

        const selectedCoordinatorsTeachersLabel =
            getOptionsSelectedTomSelectInstance(
                tomSelectCoordinatorsTeachersFilter
            );

        if (teachersCoordinators.length)
            addFilter(
                "Docentes coordinadores",
                tomSelectCoordinatorsTeachersFilter.getValue(),
                selectedCoordinatorsTeachersLabel,
                "coordinators_teachers",
                "coordinators_teachers"
            );
    }

    if (tomSelectNoCoordinatorsTeachersFilter) {
        const teachersNoCoordinators =
            tomSelectNoCoordinatorsTeachersFilter.getValue();

        const selectedNoCoordinatorsTeachersLabel =
            getOptionsSelectedTomSelectInstance(
                tomSelectNoCoordinatorsTeachersFilter
            );

        if (teachersNoCoordinators.length)
            addFilter(
                "Docentes no coordinadores",
                tomSelectNoCoordinatorsTeachersFilter.getValue(),
                selectedNoCoordinatorsTeachersLabel,
                "no_coordinators_teachers",
                "no_coordinators_teachers"
            );
    }

    if (tomSelectLearningResultsFilter) {
        const learningResults = tomSelectLearningResultsFilter.getValue();

        if (learningResults.length) {
            const selectedLearningResultsLabel =
                getOptionsSelectedTomSelectInstance(
                    tomSelectLearningResultsFilter
                );

            addFilter(
                "Resultados de aprendizaje",
                tomSelectLearningResultsFilter.getValue(),
                selectedLearningResultsLabel,
                "learning_results",
                "learning_results"
            );
        }
    }

    if (tomSelectCreatorsFilter) {
        const creators = tomSelectCreatorsFilter.items;

        const selectedCreatorsLabel = getOptionsSelectedTomSelectInstance(
            tomSelectCreatorsFilter
        );

        if (creators.length) {
            addFilter(
                "Creadores",
                tomSelectCreatorsFilter.getValue(),
                selectedCreatorsLabel,
                "creators",
                "creator_user_uid"
            );
        }
    }

    if (tomSelectCourseStatusesFilter) {
        const courseStatuses = tomSelectCourseStatusesFilter.getValue();

        const selectedCourseStatusesLabel = getOptionsSelectedTomSelectInstance(
            tomSelectCourseStatusesFilter
        );

        if (courseStatuses.length) {
            addFilter(
                "Estados",
                tomSelectCourseStatusesFilter.getValue(),
                selectedCourseStatusesLabel,
                "course_statuses",
                "course_statuses"
            );
        }
    }

    if (tomSelectCallsFilter) {
        const calls = tomSelectCallsFilter.getValue();

        const selectedCallsLabel =
            getOptionsSelectedTomSelectInstance(tomSelectCallsFilter);

        if (calls.length) {
            addFilter(
                "Convocatorias",
                tomSelectCallsFilter.getValue(),
                selectedCallsLabel,
                "calls",
                "calls"
            );
        }
    }

    if (tomSelectCourseTypesFilter) {
        const courseTypes = tomSelectCourseTypesFilter.getValue();

        const selectedCourseTypesLabel = getOptionsSelectedTomSelectInstance(
            tomSelectCourseTypesFilter
        );

        if (courseTypes.length) {
            addFilter(
                "Tipos de curso",
                tomSelectCourseTypesFilter.getValue(),
                selectedCourseTypesLabel,
                "course_types",
                "course_types"
            );
        }
    }

    const filterMinRequiredStudents = document.getElementById(
        "filter_min_required_students"
    ).value;

    if (filterMinRequiredStudents !== "") {
        addFilter(
            "Mínimo estudiantes requeridos",
            filterMinRequiredStudents,
            filterMinRequiredStudents,
            "filter_min_required_students",
            "min_required_students"
        );
    }

    const filterMaxRequiredStudents = document.getElementById(
        "filter_max_required_students"
    ).value;

    if (filterMaxRequiredStudents !== "") {
        addFilter(
            "Máximo estudiantes requeridos",
            filterMaxRequiredStudents,
            filterMaxRequiredStudents,
            "filter_max_required_students",
            "max_required_students"
        );
    }

    let selectElementCenter = document.getElementById("filter_center");
    if (selectElementCenter.value) {
        let selectedOptionText =
            selectElementCenter.options[selectElementCenter.selectedIndex].text;

        addFilter(
            "Centro",
            selectElementCenter.value,
            selectedOptionText,
            "filter_center",
            "center_uid"
        );
    }

    if (selectedCompetencesFilter.length) {
        addFilter(
            "Competencias seleccionadas",
            selectedCompetencesFilter,
            selectedCompetencesFilter.length,
            "filter_competences",
            "competences"
        );
    }

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

    if (filterKey == "calls") tomSelectCallsFilter.clear();
    else if (filterKey == "course_statuses")
        tomSelectCourseStatusesFilter.clear();
    else if (filterKey == "course_types") tomSelectCourseTypesFilter.clear();
    else if (filterKey == "creators") tomSelectCreatorsFilter.clear();
    else if (filterKey == "filter_inscription_date")
        flatpickrInscriptionDate.clear();
    else if (filterKey == "filter_realization_date")
        flatpickrRealizationDate.clear();
    else if (filterKey == "coordinators_teachers")
        tomSelectCoordinatorsTeachersFilter.clear();
    else if (filterKey == "no_coordinators_teachers")
        tomSelectNoCoordinatorsTeachersFilter.clear();
    else if (filterKey == "learning_results")
        tomSelectLearningResultsFilter.clear();
    else document.getElementById(filterKey).value = "";

    showFilters();
    drawTable();
    drawGraph();
}

function sendData() {
    const formData = new FormData();

    formData.append("filter_date", filterDate);
    formData.append("filter_type", filterType);
    formData.append("course_uid", courseUid);

    const params = {
        url: "/analytics/courses/get_courses_data",
        method: "POST",
        body: formData,
        loader: true,
    };

    apiFetch(params).then((data) => {
        drawGraphCourse(data);
        let date = data["filter_date"].split(",");

        flatpickrDateFilter.setDate([
            convertirFechas(date[0]),
            convertirFechas(date[1]),
        ]);
        document.getElementById("filter_type").value = data["date_format"];
    });
}

function drawGraphCourse(datas) {
    document.getElementById("first_graph").innerHTML = "";

    // Limpiar el contenedor y actualizar datos adicionales
    if (
        datas.last_access.access_date != "" &&
        datas.last_access.user_name != ""
    ) {
        document.getElementById("last_access").innerHTML =
            formatDateTime(datas.last_access.access_date) +
            " | " +
            datas.last_access.user_name;
    }
    if (
        datas.last_visit.access_date != "" &&
        datas.last_visit.user_name != ""
    ) {
        document.getElementById("last_visit").innerHTML =
            formatDateTime(datas.last_visit.access_date) +
            " | " +
            datas.last_visit.user_name;
    }
    document.getElementById("unique_users").innerHTML = datas.different_users;
    document.getElementById("insribed_users").innerHTML = datas.inscribed_users;

    // Definir márgenes para el gráfico y la leyenda
    const margin = { top: 30, right: 30, bottom: 70, left: 50 },
        width =
            d3.select("#first_graph").node().getBoundingClientRect().width -
            margin.left -
            margin.right,
        height = 400 - margin.top - margin.bottom;

    // Crear el SVG y el contenedor gráfico
    const svg = d3
        .select("#first_graph")
        .append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom + 50)
        .append("g")
        .attr("transform", `translate(${margin.left},${margin.top})`);

    // Preparar los datos y definir colores
    let newData;
    if (datas["accesses"][0].length == 0) {
        newData = datas["visits"][0].map((item1) => ({
            group: item1.access_date_group,
            Accesos: 0,
            Visitas: item1.access_count || 0,
        }));
    } else if (datas["visits"][0].length == 0) {
        newData = datas["accesses"][0].map((item1) => ({
            group: item1.access_date_group,
            Accesos: item1.access_count || 0,
            Visitas: 0,
        }));
    } else {
        newData = datas["accesses"][0].map((item1) => {
            const item2 = datas["visits"][0].find(
                (item) => item.access_date_group === item1.access_date_group
            );
            return {
                group: item1.access_date_group,
                Accesos: item1.access_count || 0,
                Visitas: item2 ? item2.access_count : 0,
            };
        });
    }

    newData.columns = ["group", "Accesos", "Visitas"];
    const subgroups = ["Accesos", "Visitas"];
    const groups = newData.map((d) => d.group);

    let itemMax = datas.max_value;

    if (itemMax == 0) {
        itemMax = 1;
    }

    // Ejes X y Y
    const x = d3.scaleBand().domain(groups).range([0, width]).padding([0.2]);
    const y = d3.scaleLinear().domain([0, itemMax]).range([height, 0]);
    const xSubgroup = d3
        .scaleBand()
        .domain(subgroups)
        .range([0, x.bandwidth()])
        .padding([0.05]);

    // Color por subgrupo
    const color = d3
        .scaleOrdinal()
        .domain(subgroups)
        .range(["#2C4C7E", "#7E2C4C"]);

    // Dibujar ejes
    const xAxis = svg
        .append("g")
        .attr("transform", `translate(0,${height})`)
        .call(d3.axisBottom(x).tickSize(0));

    // Añadir líneas punteadas horizontales
    svg.append("g").call(d3.axisLeft(y));

    // Dibujar líneas punteadas horizontales
    svg
        .append("g")
        .attr("class", "grid")
        .selectAll("line")
        .data(y.ticks())
        .enter()
        .append("line")
        .attr("class", "gridline")
        .attr("x1", 0)
        .attr("x2", width)
        .attr("y1", (d) => y(d))
        .attr("y2", (d) => y(d))
        .style("stroke", "#ccc")
        .style("stroke-dasharray", "2,2");

    // Rotar los textos del eje X a 45 grados
    xAxis
        .selectAll("text")
        .attr("transform", "rotate(-45)")
        .attr("text-anchor", "end")
        .attr("dx", "-0.5em")
        .attr("dy", "0.5em"); // Ajuste aquí para bajar los textos

    // Dibujar barras con animación y tooltip
    svg.append("g")
        .selectAll("g")
        .data(newData)
        .enter()
        .append("g")
        .attr("transform", (d) => `translate(${x(d.group)},0)`)
        .selectAll("rect")
        .data((d) => subgroups.map((key) => ({ key: key, value: d[key] })))
        .enter()
        .append("rect")
        .attr("x", (d) => xSubgroup(d.key))
        .attr("y", height)
        .attr("width", xSubgroup.bandwidth())
        .attr("height", 0)
        .attr("fill", (d) => color(d.key))
        .transition()
        .duration(800)
        .attr("y", function (d) {
            return y(d.value);
        })
        .attr("height", function (d) {
            return height - y(d.value);
        });

    // Crear la leyenda con icono de información y tooltip
    const legendContainer = d3
        .select("#first_graph")
        .append("div")
        .style("display", "flex")
        .style("justify-content", "space-around")
        .style("position", "relative"); // Asegura que los tooltips se posicionen bien

    // Datos de la leyenda con información adicional
    const legendData = [
        {
            name: "Accesos",
            color: "#2C4C7E",
            info: "Número de accesos de alumnos matriculados al curso desde su perfil.",
        },
        {
            name: "Visitas",
            color: "#7E2C4C",
            info: "Número de visitas realizadas por los usuarios desde fuera de su perfil.",
        },
    ];

    // Crear los elementos de la leyenda con el ícono de información
    legendContainer
        .selectAll(".legend-item")
        .data(legendData)
        .enter()
        .append("div")
        .attr("class", "legend-item")
        .style("display", "flex")
        .style("align-items", "center")
        .style("position", "relative") // Necesario para posicionar el tooltip
        .style("margin-right", "20px")
        .html(
            (d) => `
            <div style="width: 20px; height: 20px; background-color: ${
                d.color
            }; margin-right: 5px;"></div>
            ${d.name}
            <span class="tooltip-i" style="margin-left: 8px; cursor: pointer; color: white;">${heroicon(
                "tooltip-i",
                "outline"
            )}</span>
            <div class="tooltip" style="border: 1px solid black; visibility: hidden; background-color: white; color: black; text-align: center; border-radius: 5px; padding: 5px; position: absolute; bottom: 125%; left: 50%; transform: translateX(-50%); white-space: nowrap; z-index: 1; opacity: 0; transition: opacity 0.3s;">
                ${d.info}
            </div>
        `
        );

    // Añadir interacción para mostrar el tooltip al pasar el ratón sobre la "i"
    legendContainer
        .selectAll(".legend-item")
        .on("mouseover", function () {
            d3.select(this)
                .select(".tooltip")
                .style("visibility", "visible")
                .style("opacity", "1");
        })
        .on("mouseout", function () {
            d3.select(this)
                .select(".tooltip")
                .style("visibility", "hidden")
                .style("opacity", "0");
        });
}

function graficar(data, quantity, d3GraphId, d3GraphXAxis) {
    const d3GraphElement = document.getElementById(d3GraphId);
    d3GraphElement.innerHTML = "";

    const d3GraphXAxisElement = document.getElementById(d3GraphXAxis);

    if (quantity == "all") {
        d3GraphXAxisElement.classList.remove("hidden");
    } else {
        d3GraphXAxisElement.classList.add("hidden");
    }

    const itemWithMaxAccesses = data.reduce((max, current) =>
        current.count > max.count ? current : max,
        { count: 0 }  // Valor inicial
    );

    const ancho = d3GraphElement.clientWidth;

    // Obtener el ancho de la ventana para ajustar el gráfico
    let windowWidth = ancho; // Ancho completo de la página
    const margin = { top: 20, right: 30, bottom: 40, left: 300 },
        width = windowWidth - margin.left - margin.right - 40, // Ancho ajustado
        barHeight = 25, // Altura de cada barra
        maxHeight = 600; // Altura máxima fija para el contenedor con scroll

    // Definir la altura total dependiendo de la cantidad de datos
    let height = data.length * barHeight + margin.top + margin.bottom;

    // Si la cantidad es "all", limita la altura del gráfico
    if (quantity === "all" && height > maxHeight) {
        height = maxHeight; // Fijamos un alto máximo
    }

    // Ajustar el contenedor con scroll si es necesario
    const container = d3
        .select(`#${d3GraphId}`)
        .style("width", `${windowWidth}px`)
        .style("height", `${height}px`)
        .style("overflow-y", quantity === "all" ? "scroll" : "visible"); // Activar scroll solo cuando quantity es "all"

    // Crear el SVG dentro del contenedor
    const svg = container
        .append("svg")
        .attr("width", windowWidth) // Ancho completo del SVG
        .attr("height", data.length * barHeight + margin.top + margin.bottom) // Altura real sin restricciones
        .append("g")
        .attr("transform", `translate(${margin.left}, ${margin.top})`);

    // Eje X
    const x = d3
        .scaleLinear()
        .domain([0, itemWithMaxAccesses["count"] + 1])
        .range([0, width]);

    if (quantity != "all") {
        svg.append("g")
            .attr("transform", `translate(0, ${data.length * barHeight})`)
            .call(d3.axisBottom(x));
    }

    // Eje Y
    const y = d3
        .scaleBand()
        .range([0, data.length * barHeight])
        .domain(data.map((d) => d.title))
        .padding(0.1);

    svg.append("g").call(d3.axisLeft(y));

    // Crear el tooltip
    const tooltip = d3
        .select("#tooltip_1")
        .append("div")
        .style("opacity", 0)
        .attr("class", "tooltip")
        .style("background-color", "white")
        .style("border", "solid")
        .style("border-width", "1px")
        .style("border-radius", "5px")
        .style("padding", "10px")
        .style("position", "absolute")
        .style("pointer-events", "none"); // Evitar que el tooltip interfiera con el mouse

    // Funciones para el tooltip
    const mouseover = function (event, d) {
        tooltip.html(`${d.title}: ${d.count}`).style("opacity", 1);
    };

    const mousemove = function (event) {
        tooltip
            .style("left", event.pageX + 20 + "px") // Posición horizontal según el ratón
            .style("top", event.pageY - 40 + "px"); // Posición vertical según el ratón
    };

    const mouseleave = function () {
        tooltip.style("opacity", 0);
    };

    // Dibujar barras con animación
    svg.selectAll("rect")
        .data(data)
        .join("rect")
        .attr("x", x(0))
        .attr("y", (d) => y(d.title))
        .attr("width", 0) // Comenzar desde el ancho 0 para la animación
        .attr("height", y.bandwidth())
        .attr("fill", "#2C4C7E")
        .transition() // Transición para animar las barras
        .duration(800)
        .attr("width", (d) => x(d.count)); // Ampliar a su ancho final

    // Añadir interacciones para el tooltip
    svg.selectAll("rect")
        .on("mouseover", mouseover)
        .on("mousemove", mousemove)
        .on("mouseleave", mouseleave);

    if (quantity == "all") {
        const xAxisSvg = d3
            .select(`#${d3GraphXAxis}`)
            .append("svg")
            .attr("width", windowWidth)
            .attr("height", 40);

        xAxisSvg
            .append("g")
            .attr("transform", `translate(${margin.left}, 20)`)
            .call(d3.axisBottom(x));
    }
}

function graficarTreeMap(allDatas) {

    let primerValor;
    if (allDatas.length){
        primerValor = {
            name: "origin",
            value: "", // Ajusta el valor según sea necesario
            parent: "",
        };

        const data = [
            primerValor,
            ...allDatas.map((element, index) => ({
                name: element.title,
                value: parseInt(element.count),
                parent: "origin",
            })),
        ];

        const colorTreemap = d3
            .scaleOrdinal()
            .domain(data.map((d) => d.name)) // Definir dominio según los nombres
            .range([
                "#507ab9",
                "#2C4C7E",
                "#7E5E2C",
                "#7E2C4C",
                "#2C7E5E",
                "#B39264",
            ]); // Lista de colores personalizados

        // Obtener el ancho del contenedor
        const container = document.getElementById("d3_graph_treemap");
        const anchoContenedor = container.clientWidth; // Ancho del contenedor
        const margin = { top: 10, right: 10, bottom: 10, left: 10 };
        const width = anchoContenedor - margin.left - margin.right; // Ancho ajustado del gráfico
        const height = 445 - margin.top - margin.bottom; // Altura fija o ajustada si es necesario

        // Limpiar el contenedor
        d3.select("#d3_graph_treemap").selectAll("*").remove();

        // Append the svg object to the body of the page
        const svg = d3
            .select("#d3_graph_treemap")
            .append("svg")
            .attr("width", width + margin.left + margin.right)
            .attr("height", height + margin.top + margin.bottom)
            .append("g")
            .attr("transform", `translate(${margin.left}, ${margin.top})`);

        // Stratify the data: reformatting for d3.js
        const root = d3
            .stratify()
            .id(function (d) {
                return d.name;
            })
            .parentId(function (d) {
                return d.parent;
            })(data);
        root.sum(function (d) {
            return +d.value;
        });

        // Compute the position of each element of the hierarchy
        d3.treemap().size([width, height]).padding(4)(root);

        // Define a color scale
        d3.scaleOrdinal(d3.schemeCategory10); // Usar una escala de colores predefinida o personalizada

        // Use this information to add rectangles:
        svg.selectAll("rect")
            .data(root.leaves())
            .join("rect")
            .attr("x", function (d) {
                return d.x0;
            })
            .attr("y", function (d) {
                return d.y0;
            })
            .attr("width", function (d) {
                return d.x1 - d.x0;
            })
            .attr("height", function (d) {
                return d.y1 - d.y0;
            })
            .style("stroke", "black")
            .style("fill", function (d) {
                return colorTreemap(d.data.name);
            }) // Apply the color scale here
            .on("mouseover", function (event, d) {
                const tooltip = d3.select("#tooltip");
                tooltip
                    .style("display", "block")
                    .html(`Curso: ${d.data.name}<br>Accesos: ${d.data.value}`)
                    .style("left", event.pageX + 40 + "px")
                    .style("top", event.pageY - 80 + "px");
            })
            .on("mouseout", function () {
                d3.select("#tooltip").style("display", "none");
            });

        // And to add the text labels
        svg.selectAll("text")
            .data(root.leaves())
            .join("text")
            .attr("x", function (d) {
                const text = d.data.name;
                const textWidth = getTextWidth(text, "12px");
                const rectWidth = d.x1 - d.x0;
                return rectWidth > textWidth
                    ? d.x0 + (rectWidth - textWidth) / 2
                    : -9999; // Hide text if not enough space
            })
            .attr("y", function (d) {
                const text = d.data.name;
                const textHeight = getTextHeight(text, "12px");
                const rectHeight = d.y1 - d.y0;
                return rectHeight > textHeight
                    ? d.y0 + (rectHeight + textHeight) / 2
                    : -9999; // Hide text if not enough space
            })
            .text(function (d) {
                const text = d.data.name;
                const textWidth = getTextWidth(text, "12px");
                const textHeight = getTextHeight(text, "12px");
                const rectWidth = d.x1 - d.x0;
                const rectHeight = d.y1 - d.y0;
                return rectWidth > textWidth && rectHeight > textHeight ? text : "";
            })
            .attr("font-size", "12px") // Adjust font size as needed
            .attr("fill", "white");

        // Utility function to calculate text width
        function getTextWidth(text, fontSize) {
            const canvas = document.createElement("canvas");
            const context = canvas.getContext("2d");
            context.font = fontSize + " sans-serif";
            return context.measureText(text).width;
        }

        // Utility function to calculate text height
        function getTextHeight(text, fontSize) {
            const canvas = document.createElement("canvas");
            const context = canvas.getContext("2d");
            context.font = fontSize + " sans-serif";
            const metrics = context.measureText(text);
            return (
                metrics.actualBoundingBoxAscent + metrics.actualBoundingBoxDescent
            );
        }
    }
}

function convertirFechas(date) {
    const fecha = new Date(date);
    const dia = String(fecha.getDate()).padStart(2, "0"); // Obtener día con dos dígitos
    const mes = String(fecha.getMonth() + 1).padStart(2, "0"); // Obtener mes (0-11) + 1
    const anio = fecha.getFullYear(); // Obtener año
    const fechaFormateada = `${dia}-${mes}-${anio}`;
    return fechaFormateada;
}
