import { heroicon } from "../heroicons.js";
import { TabulatorFull as Tabulator } from "tabulator-tables";
import {
    tabulatorBaseConfig,
    controlsPagination,
    controlsSearch,
    updatePaginationInfo,
    formatDateTime,
} from "../tabulator_handler.js";
import {
    apiFetch,
    getFlatpickrDateRangeSql,
    instanceFlatpickrNoHour,
    getMultipleTomSelectInstance,
    getOptionsSelectedTomSelectInstance,
    getCsrfToken
} from "../app.js";
import { showModal, hideModal } from "../modal_handler.js";
import * as d3 from "d3";
import * as XLSX from "xlsx";
window.XLSX = XLSX;

const endPointTableResources = "/analytics/users/get_poa_resources";

let analyticsPoaTableResources;

let flatpickrDateFilterResource;
let resourceUid;

let filterDate = "";
let filterType = "";

let tomFilterSelectCategories;

let filters = [];

document.addEventListener("DOMContentLoaded", function () {
    drawTableResources();
    drawGraphResources();

    document
        .getElementById("itemsPerGraphResources")
        .addEventListener("change", drawGraphResources);

    flatpickrDateFilterResource = instanceFlatpickrNoHour(
        "filter_date_accesses_resource"
    );

    document
        .getElementById("download-xlsx-resource")
        .addEventListener("click", function () {
            analyticsPoaTableResources.download("xlsx", "recursos.xlsx", {
                sheetName: "Recursos",
            });
        });

    document
        .getElementById("filter-educational-resources-btn")
        .addEventListener("click", function () {
            showModal("filter-educational-resources-modal");
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
});

function initializeTomSelect() {
    tomFilterSelectCategories = getMultipleTomSelectInstance(
        "#filter_select_categories"
    );
}

/**
 * Maneja el evento de clic en el botón para aplicar los filtros.
 * Recoge los filtros del modal, los muestra en la interfaz y vuelve a inicializar
 * la tabla de cursos con los nuevos filtros aplicados.
 */
function controlSaveHandlerFilters() {
    filters = collectFilters();
    showFilters();
    hideModal("filter-educational-resources-modal");
    drawTableResources();
    drawGraphResources();
}



function controlDeleteFilters(deleteBtn) {
    const filterKey = deleteBtn.getAttribute("data-filter-key");

    filters = filters.filter((filter) => filter.filterKey !== filterKey);

    if (filterKey == "Categories") tomFilterSelectCategories.clear();
    else document.getElementById(filterKey).value = "";

    showFilters();
    drawTableResources();
    drawGraphResources();
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
    let filterResourceWay = document.getElementById("filter_resource_way");

    if (filterResourceWay.value) {
        addFilter(
            "Forma de recurso",
            filterResourceWay.value,
            filterResourceWay.value == "FILE" ? "Fichero" : "URL",
            "filter_resource_way",
            "resource_way"
        );
    }

    let filterEducationalResourceTypeUid = document.getElementById(
        "filter_educational_resource_type_uid"
    );
    if (filterEducationalResourceTypeUid.value) {
        let selectedFilterOptionText =
        filterEducationalResourceTypeUid.options[
            filterEducationalResourceTypeUid.selectedIndex
            ].text;

        addFilter(
            "Tipo",
            filterEducationalResourceTypeUid.value,
            selectedFilterOptionText,
            "filter_educational_resource_type_uid",
            "educational_resource_type_uid"
        );
    }

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

    if (tomFilterSelectCategories) {
        const categoriesFilter = tomFilterSelectCategories.getValue();

        const selectedFilterCategoriesLabel =
            getOptionsSelectedTomSelectInstance(tomFilterSelectCategories);

        if (categoriesFilter.length)
            addFilter(
                "Categorías",
                categoriesFilter,
                selectedFilterCategoriesLabel,
                "Categories",
                "categories"
            );
    }

    return selectedFilters;
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

function resetFilters() {
    filters = [];
    showFilters();

    tomFilterSelectCategories.clear();
    document.getElementById("filter_resource_way").value = "";
    document.getElementById("filter_educational_resource_type_uid").value = "";
    document.getElementById("filter_embeddings").value = "";

    drawTableResources();
    drawGraphResources();
}

function drawTableResources() {
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
                showModal("analytics-resource-modal", "Datos del Recurso");
                fillDataResourceModal();
                resourceUid = data.uid;
                sendDataResources();
                document.getElementById("last_access_resource").innerHTML = "";
                document.getElementById("unique_users_resource").innerHTML = "";
                document.getElementById("last_visit_resource").innerHTML = "";
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
    ];

    const { ...tabulatorBaseConfigOverrided } = tabulatorBaseConfig;

    analyticsPoaTableResources = new Tabulator("#analytics-poa-resources", {
        ...tabulatorBaseConfigOverrided,
        ajaxURL: endPointTableResources,
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
            updatePaginationInfo(
                analyticsPoaTableResources,
                response,
                "analytics-poa-resources"
            );
            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });

    controlsSearch(
        analyticsPoaTableResources,
        endPointTableResources,
        "analytics-poa-resources"
    );
    controlsPagination(analyticsPoaTableResources, "analytics-poa-resources");
}

function fillDataResourceModal() {
    const filterDataInput = document.querySelector(
        "#filter_date_accesses_resource"
    );
    const filterTypeInput = document.querySelector("#filter_type_resource");

    filterDataInput.addEventListener("change", function () {
        filterDate = getFlatpickrDateRangeSql(flatpickrDateFilterResource);
        if (filterDate.length > 1) {
            const startDate = new Date(filterDate[0]);
            const endDate = new Date(filterDate[1]);
            const diffInMs = endDate - startDate;
            const diffInDays = diffInMs / (1000 * 60 * 60 * 24);
            const days = Math.ceil(diffInDays);
            if (days > 20) {
                filterType = "YYYY-MM";
                document.getElementById("filter_type_resource").value =
                    "YYYY-MM";
            } else if (days > 365) {
                filterType = "YYYY";
                document.getElementById("filter_type_resource").value = "YYYY";
            }

            sendDataResources();
        }
    });

    filterTypeInput.addEventListener("change", function () {
        filterType = document.getElementById("filter_type_resource").value;
        if (filterType != null) {
            sendDataResources();
        }
    });
}

function sendDataResources() {
    const formData = new FormData();

    formData.append("filter_date_resource", filterDate);
    formData.append("filter_type_resource", filterType);
    formData.append("educational_resource_uid", resourceUid);

    const params = {
        url: "/analytics/users/get_resources_data",
        method: "POST",
        body: formData,
        loader: true,
    };

    apiFetch(params).then((data) => {
        drawGraphResource(data);
        let date = data["filter_date"].split(",");
        flatpickrDateFilterResource.setDate([
            convertirFechas(date[0]),
            convertirFechas(date[1]),
        ]);
        document.getElementById("filter_type_resource").value =
            data["date_format"];
    });
}

function drawGraphResource(datas) {
    document.getElementById("second_graph").innerHTML = "";

    // Limpiar el contenedor y actualizar datos adicionales
    if (
        datas.last_access.access_date != "" &&
        datas.last_access.user_name != ""
    ) {
        document.getElementById("last_access_resource").innerHTML =
            formatDateTime(datas.last_access.access_date) +
            " | " +
            datas.last_access.user_name;
    }
    if (datas.last_visit.date != "") {
        document.getElementById("last_visit_resource").innerHTML =
            formatDateTime(datas.last_visit.access_date);
    }
    document.getElementById("unique_users_resource").innerHTML =
        datas.different_users;

    // Definir márgenes para el gráfico y la leyenda
    const margin = { top: 30, right: 30, bottom: 70, left: 50 },
        width =
            d3.select("#second_graph").node().getBoundingClientRect().width -
            margin.left -
            margin.right,
        height = 400 - margin.top - margin.bottom;

    // Crear el SVG y el contenedor gráfico
    const svg = d3
        .select("#second_graph")
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
        .select("#second_graph")
        .append("div")
        .style("display", "flex")
        .style("justify-content", "space-around")
        .style("position", "relative"); // Asegura que los tooltips se posicionen bien

    // Datos de la leyenda con información adicional
    const legendData = [
        {
            name: "Accesos",
            color: "#2C4C7E",
            info: "Número de accesos de alumnos registrados.",
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

function drawGraphResources() {
    const params = {
        url: "/analytics/users/get_poa_graph_resources",
        method: "POST",
        body: {
            filters
        },
        loader: true,
        stringify: true
    };
    apiFetch(params).then((data) => {
        const quantity = document.getElementById(
            "itemsPerGraphResources"
        ).value;
        graficarResources(data, quantity);
        graficarTreeMapResources(data);
    });
}

function graficarResources(allDatas, quantity) {
    document.getElementById("d3_graph_resources").innerHTML = "";
    document.getElementById("d3_graph_resources_x_axis").innerHTML = "";

    let datas;
    if (quantity === "all") {
        datas = allDatas;
        document
            .getElementById("d3_graph_resources_x_axis")
            .classList.remove("hidden");
    } else {
        datas = allDatas.slice(0, quantity);
        document
            .getElementById("d3_graph_resources_x_axis")
            .classList.add("hidden");
    }

    const newDatas = datas.map((element, index) => ({
        title: index + 1 + " " + element.title.substring(0, 50),
        count: parseInt(element.accesses_count),
    }));

    const itemWithMaxAccesses = newDatas.reduce((max, current) =>
        current.count > max.count ? current : max,
        { count: 0 }  // Valor inicial
    );

    const div = document.getElementById("d3_graph_resources");
    const ancho = div.clientWidth;

    // Definir márgenes y dimensiones
    const margin = { top: 20, right: 30, bottom: 40, left: 200 },
        width = ancho - margin.left - margin.right,
        barHeight = 25, // Altura de cada barra
        maxHeight = 400; // Altura máxima fija cuando quantity es "all"

    // Definir la altura total dependiendo de la cantidad de datos
    let height = newDatas.length * barHeight + margin.top + margin.bottom;

    // Si quantity es "all", limitar la altura y activar el scroll
    if (quantity === "all" && height > maxHeight) {
        height = maxHeight; // Fijar un alto máximo para el contenedor
    }

    // Seleccionar el contenedor del gráfico y ajustar el scroll si es necesario
    const container = d3
        .select("#d3_graph_resources")
        .style("width", `${width + margin.left + margin.right}px`) // Ancho ajustado
        .style("height", `${height}px`) // Altura ajustada con scroll si es necesario
        .style("overflow-y", quantity === "all" ? "scroll" : "visible"); // Activar scroll solo si quantity es "all"

    // Crear el SVG
    const svg = container
        .append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr(
            "height",
            newDatas.length * barHeight + margin.top + margin.bottom
        ) // Altura real sin restricciones
        .append("g")
        .attr("transform", `translate(${margin.left}, ${margin.top})`);

    // Eje X
    const x = d3
        .scaleLinear()
        .domain([0, itemWithMaxAccesses["count"] + 1])
        .range([0, width]);

    if (quantity !== "all") {
        // Dibujar eje X directamente en el gráfico si quantity no es "all"
        svg.append("g")
            .attr("transform", `translate(0, ${newDatas.length * barHeight})`)
            .call(d3.axisBottom(x))
            .selectAll("text")
            .attr("transform", "translate(-10,0)rotate(-45)")
            .style("text-anchor", "end");
    } else {
        // Si quantity es "all", dibujar el eje X en el contenedor separado
        const xAxisContainer = d3
            .select("#d3_graph_resources_x_axis")
            .append("svg")
            .attr("width", width + margin.left + margin.right)
            .attr("height", 60) // Altura suficiente para el eje y los textos rotados
            .append("g")
            .attr("transform", `translate(${margin.left}, 20)`); // Ajustar la posición del eje X dentro del nuevo SVG

        xAxisContainer
            .append("g")
            .call(d3.axisBottom(x))
            .selectAll("text")
            .attr("transform", "translate(-10,0)rotate(-45)") // Rotar los textos del eje X
            .style("text-anchor", "end");
    }

    // Eje Y
    const y = d3
        .scaleBand()
        .range([0, newDatas.length * barHeight]) // Ajustar el rango Y según la cantidad de datos
        .domain(newDatas.map((d) => d.title))
        .padding(0.1);

    svg.append("g").call(d3.axisLeft(y));

    // Crear el tooltip
    const tooltip = d3
        .select("body")
        .append("div")
        .style("opacity", 0)
        .attr("class", "tooltip")
        .style("background-color", "white")
        .style("border", "solid")
        .style("border-width", "1px")
        .style("border-radius", "5px")
        .style("padding", "10px")
        .style("position", "absolute")
        .style("pointer-events", "none"); // Evitar interacción con el mouse

    // Funciones para el tooltip
    const mouseover = function (event, d) {
        tooltip.html(`Accesos: ${d.count}`).style("opacity", 1);
    };

    const mousemove = function (event) {
        tooltip
            .style("left", event.pageX + 20 + "px") // Posición horizontal según el ratón
            .style("top", event.pageY - 40 + "px"); // Posición vertical según el ratón
    };

    const mouseleave = function () {
        tooltip.style("opacity", 0);
    };

    // Dibujar barras con animación y tooltip
    svg.selectAll("rect")
        .data(newDatas)
        .join("rect")
        .attr("x", x(0))
        .attr("y", (d) => y(d.title))
        .attr("width", 0) // Iniciar con ancho cero para la animación
        .attr("height", y.bandwidth())
        .attr("fill", "#2C4C7E")
        .on("mouseover", mouseover)
        .on("mousemove", mousemove)
        .on("mouseleave", mouseleave)
        .transition() // Añadir transición
        .duration(1000) // Duración de la animación
        .attr("width", (d) => x(d.count)); // Ancho final de las barras
}

function graficarTreeMapResources(allDatas) {
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
                value: parseInt(element.accesses_count),
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
        const container = document.getElementById("d3_graph_treemap_resources");
        const anchoContenedor = container.clientWidth; // Ancho del contenedor
        const margin = { top: 10, right: 10, bottom: 10, left: 10 };
        const width = anchoContenedor - margin.left - margin.right; // Ancho ajustado del gráfico
        const height = 445 - margin.top - margin.bottom; // Altura fija o ajustada si es necesario

        // Limpiar el contenedor
        d3.select("#d3_graph_treemap_resources").selectAll("*").remove();

        // Append the svg object to the body of the page
        const svg = d3
            .select("#d3_graph_treemap_resources")
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
                    .style("left", event.pageX + 5 + "px")
                    .style("top", event.pageY - 28 + "px");
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
