import { heroicon } from "../heroicons.js";
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
    showFormErrors,
    instanceFlatpickr,
    getFlatpickrDateRangeSql,
 } from "../app.js";
 import { showModal } from "../modal_handler";
 import * as d3 from 'd3';
 import * as XLSX from 'xlsx';
 window.XLSX = XLSX;


const endPointTable = "/analytics/users/get_poa_get";
const endPointTableAccesses = "/analytics/users/get_poa_accesses";
const endPointTableResources = "/analytics/users/get_poa_resources";
const endPointTableResourcesAccesses = "/analytics/users/get_poa_resources_accesses";

let analyticsPoaTable;
let analyticsPoaAccessesTable;
let analyticsPoaTableResources;
let analyticsPoaTableResourcesAccesses;

let flatpickrDateFilter;
let flatpickrDateFilterResource;
let course_uid;
let resource_uid;

let filter_date = "";
let filter_type = "";

document.addEventListener("DOMContentLoaded", function () {

    drawTable();
    drawGraph(10);
    //drawTableAccesses();

    drawTableResources();
    //drawTableResourcesAccesses();
    drawGraphResources();

    document
        .getElementById("itemsPerGraph")
        .addEventListener('change', drawGraph);

    document
        .getElementById("itemsPerGraphResources")
        .addEventListener('change', drawGraphResources);

    flatpickrDateFilter = instanceFlatpickr("filter_date_accesses");
    flatpickrDateFilterResource = instanceFlatpickr("filter_date_accesses_resource");

    document.getElementById("download-xlsx-course").addEventListener("click", function(){
        analyticsPoaTable.download("xlsx", "cursos.xlsx", {sheetName:"Cursos"});
    });

    document.getElementById("download-xlsx-resource").addEventListener("click", function(){
        analyticsPoaTableResources.download("xlsx", "recursos.xlsx", {sheetName:"Recursos"});
    });

});

function drawTable(){
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
                    <button type="button" class='btn action-btn'>${heroicon(
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
                course_uid = data.uid;
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
    analyticsPoaTable = new Tabulator("#analytics-poa", {
        ajaxURL: endPointTable,
        ajaxConfig: "GET",
        ...tabulatorBaseConfig,
        ajaxResponse: async function (url, params, response) {
            updatePaginationInfo(
                analyticsPoaTable,
                response,
                "analytics-poa"
            );
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
function fillDataCourseModal(){
    const filter_data_input = document.querySelector('#filter_date_accesses');
    const filter_type_input = document.querySelector('#filter_type');

    filter_data_input.addEventListener("change", function () {
        filter_date = getFlatpickrDateRangeSql(flatpickrDateFilter);
        if (filter_date.length > 1){
            const startDate = new Date(filter_date[0]);
            const endDate = new Date(filter_date[1]);
            const diffInMs = endDate - startDate;
            const diffInDays = diffInMs / (1000 * 60 * 60 * 24);
            const days = Math.ceil(diffInDays);
            if (days > 20){
                filter_type = 'YYYY-MM';
                document.getElementById("filter_type").value = 'YYYY-MM';
            } else if (days > 365){
                filter_type = 'YYYY';
                document.getElementById("filter_type").value = 'YYYY';
            }

            sendData();
        }

    });

    filter_type_input.addEventListener("change", function () {
        filter_type = document.getElementById("filter_type").value;
        if (filter_type != null){
            sendData();
        }

    });
}
function sendData(){
    const formData = new FormData();

    formData.append("filter_date", filter_date);
    formData.append("filter_type", filter_type);
    formData.append("course_uid", course_uid);

    const params = {
        url: "/analytics/users/get_courses_data",
        method: "POST",
        body: formData,
        loader: true,
    };

    apiFetch(params)
        .then((data) => {
            drawGraphCourse(data);
            let date = data['filter_date'].split(",");
            flatpickrDateFilter.setDate([convertirFechas(date[0]), convertirFechas(date[1])]);
            document.getElementById("filter_type").value = data['date_format'];
        });
}

function drawGraphCourse(datas) {
    document.getElementById("first_graph").innerHTML = "";

    // Limpiar el contenedor y actualizar datos adicionales
    if (datas.last_access.access_date != "" && datas.last_access.user_name != "") {
        document.getElementById("last_access").innerHTML = formatDateTime(datas.last_access.access_date) + " " + datas.last_access.user_name;
    }
    if (datas.last_visit.access_date != "" && datas.last_visit.user_name != "") {
        document.getElementById("last_visit").innerHTML = formatDateTime(datas.last_visit.access_date) + " " + datas.last_visit.user_name;
    }
    document.getElementById("unique_users").innerHTML = datas.different_users;
    document.getElementById("insribed_users").innerHTML = datas.inscribed_users;

    // Definir márgenes para el gráfico y la leyenda
    const margin = {top: 30, right: 30, bottom: 70, left: 50},
        width = d3.select("#first_graph").node().getBoundingClientRect().width - margin.left - margin.right,
        height = 400 - margin.top - margin.bottom;

    // Crear el SVG y el contenedor gráfico
    const svg = d3.select("#first_graph")
        .append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom + 50)
        .append("g")
        .attr("transform", `translate(${margin.left},${margin.top})`);

    // Preparar los datos y definir colores
    let new_data;
    if (datas['accesses'][0].length == 0) {
        new_data = datas['visits'][0].map(item1 => ({
            group: item1.access_date_group,
            Accesos: 0,
            Visitas: item1.access_count || 0
        }));
    } else if (datas['visits'][0].length == 0) {
        new_data = datas['accesses'][0].map(item1 => ({
            group: item1.access_date_group,
            Accesos: item1.access_count || 0,
            Visitas: 0
        }));
    } else {
        new_data = datas['accesses'][0].map(item1 => {
            const item2 = datas['visits'][0].find(item => item.access_date_group === item1.access_date_group);
            return {
                group: item1.access_date_group,
                Accesos: item1.access_count || 0,
                Visitas: item2 ? item2.access_count : 0
            };
        });
    }

    new_data.columns = ["group", "Accesos", "Visitas"];
    const subgroups = ["Accesos", "Visitas"];
    const groups = new_data.map(d => d.group);

    let itemMax = datas.max_value;

    if (itemMax == 0){
        itemMax = 1;
    }

    // Ejes X y Y
    const x = d3.scaleBand().domain(groups).range([0, width]).padding([0.2]);
    const y = d3.scaleLinear().domain([0, itemMax]).range([height, 0]);
    const xSubgroup = d3.scaleBand().domain(subgroups).range([0, x.bandwidth()]).padding([0.05]);

    // Color por subgrupo
    const color = d3.scaleOrdinal().domain(subgroups).range(['#2C4C7E', '#7E2C4C']);

    // Dibujar ejes
    const xAxis = svg.append("g").attr("transform", `translate(0,${height})`).call(d3.axisBottom(x).tickSize(0));

    // Añadir líneas punteadas horizontales
    const yAxis = svg.append("g").call(d3.axisLeft(y));

    // Dibujar líneas punteadas horizontales
    const gridlines = svg.append("g")
        .attr("class", "grid")
        .selectAll("line")
        .data(y.ticks())
        .enter().append("line")
        .attr("class", "gridline")
        .attr("x1", 0)
        .attr("x2", width)
        .attr("y1", d => y(d))
        .attr("y2", d => y(d))
        .style("stroke", "#ccc")
        .style("stroke-dasharray", "2,2");

    // Rotar los textos del eje X a 45 grados
    xAxis.selectAll("text")
        .attr("transform", "rotate(-45)")
        .attr("text-anchor", "end")
        .attr("dx", "-0.5em")
        .attr("dy", "0.5em"); // Ajuste aquí para bajar los textos

    // Dibujar barras con animación y tooltip
    svg.append("g").selectAll("g")
        .data(new_data)
        .enter().append("g")
        .attr("transform", d => `translate(${x(d.group)},0)`)
        .selectAll("rect")
        .data(d => subgroups.map(key => ({key: key, value: d[key]})))
        .enter().append("rect")
        .attr("x", d => xSubgroup(d.key))
        .attr("y", height)
        .attr("width", xSubgroup.bandwidth())
        .attr("height", 0)
        .attr("fill", d => color(d.key))
        .transition()
        .duration(800)
        .attr("y", function (d) {
            return y(d.value);
        })
        .attr("height", function (d) {
            return height - y(d.value);
        });

    // Crear la leyenda con icono de información y tooltip
    const legendContainer = d3.select("#first_graph")
        .append("div")
        .style("display", "flex")
        .style("justify-content", "space-around")
        .style("position", "relative"); // Asegura que los tooltips se posicionen bien

    // Datos de la leyenda con información adicional
    const legendData = [
        { name: "Accesos", color: "#2C4C7E", info: "Número de accesos de alumnos matriculados al curso desde su perfil." },
        { name: "Visitas", color: "#7E2C4C", info: "Número de visitas realizadas por los usuarios desde fuera de su perfil." }
    ];

    // Crear los elementos de la leyenda con el ícono de información
    legendContainer.selectAll(".legend-item")
        .data(legendData)
        .enter().append("div")
        .attr("class", "legend-item")
        .style("display", "flex")
        .style("align-items", "center")
        .style("position", "relative")  // Necesario para posicionar el tooltip
        .style("margin-right", "20px")
        .html(d => `
            <div style="width: 20px; height: 20px; background-color: ${d.color}; margin-right: 5px;"></div>
            ${d.name}
            <span class="tooltip-i" style="margin-left: 8px; cursor: pointer; color: white;">${heroicon("tooltip-i", "outline")}</span>
            <div class="tooltip" style="border: 1px solid black; visibility: hidden; background-color: white; color: black; text-align: center; border-radius: 5px; padding: 5px; position: absolute; bottom: 125%; left: 50%; transform: translateX(-50%); white-space: nowrap; z-index: 1; opacity: 0; transition: opacity 0.3s;">
                ${d.info}
            </div>
        `);

    // Añadir interacción para mostrar el tooltip al pasar el ratón sobre la "i"
    legendContainer.selectAll(".legend-item")
        .on("mouseover", function() {
            d3.select(this).select(".tooltip")
                .style("visibility", "visible")
                .style("opacity", "1");
        })
        .on("mouseout", function() {
            d3.select(this).select(".tooltip")
                .style("visibility", "hidden")
                .style("opacity", "0");
        });
}





function drawTableAccesses(){
    const columns = [
        { title: "Título", field: "title", widthGrow: 8 },
        {
            title: "Fecha de primer acceso",
            field: "first_access",
            formatter: function (cell, formatterParams, onRendered) {
                const isoDate = cell.getValue();
                if (!isoDate) return "";
                return formatDateTime(isoDate);
            },
            widthGrow: 2,
        },
        {
            title: "Fecha de último acceso",
            field: "last_access",
            formatter: function (cell, formatterParams, onRendered) {
                const isoDate = cell.getValue();
                if (!isoDate) return "";
                return formatDateTime(isoDate);
            },
            widthGrow: 2,
        },
    ];
    analyticsPoaAccessesTable = new Tabulator("#analytics-poa-accesses", {
        ajaxURL: endPointTableAccesses,
        ajaxConfig: "GET",
        ...tabulatorBaseConfig,
        ajaxResponse: async function (url, params, response) {
            updatePaginationInfo(
                analyticsPoaAccessesTable,
                response,
                "analytics-poa-accesses"
            );
            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });

    controlsSearch(analyticsPoaAccessesTable, endPointTableAccesses, "analytics-poa-accesses");
    controlsPagination(analyticsPoaAccessesTable, "analytics-poa-accesses");
}
function drawGraph(){
    //get data
    let datas;
    const params = {
        url: "/analytics/users/get_poa_graph",
        method: "GET"
    };
    apiFetch(params).then((data) => {
        const quantity = document.getElementById("itemsPerGraph").value;
        graficar(data, quantity);
        graficarTreeMap(data);
    });
}

function graficar(allDatas, quantity) {
    document.getElementById("d3_graph").innerHTML = "";
    let datas;
    if (quantity == "all") {
        datas = allDatas;
        document.getElementById("d3_graph_x_axis").classList.remove("hidden");
    } else {
        datas = allDatas.slice(0, quantity);
        document.getElementById("d3_graph_x_axis").classList.add("hidden");
    }

    const new_datas = datas.map((element, index) => ({
        title: (index + 1) + " " + element.title.substring(0, 50),
        count: parseInt(element.accesses_count)
    }));

    const itemWithMaxAccesses = new_datas.reduce((max, current) =>
        current.count > max.count ? current : max
    );

    var div = document.getElementById('d3_graph');
    var ancho = div.clientWidth;

    // Obtener el ancho de la ventana para ajustar el gráfico
    let windowWidth = ancho; // Ancho completo de la página
    const margin = { top: 20, right: 30, bottom: 40, left: 300 },
        width = windowWidth - margin.left - margin.right - 40, // Ancho ajustado
        barHeight = 25, // Altura de cada barra
        maxHeight = 600; // Altura máxima fija para el contenedor con scroll

    // Definir la altura total dependiendo de la cantidad de datos
    let height = new_datas.length * barHeight + margin.top + margin.bottom;

    // Si la cantidad es "all", limita la altura del gráfico
    if (quantity === "all" && height > maxHeight) {
        height = maxHeight; // Fijamos un alto máximo
    }

    // Ajustar el contenedor con scroll si es necesario
    const container = d3.select("#d3_graph")
        .style("width", `${windowWidth}px`)
        .style("height", `${height}px`)
        .style("overflow-y", (quantity === "all" ? "scroll" : "visible")); // Activar scroll solo cuando quantity es "all"

    // Crear el SVG dentro del contenedor
    const svg = container.append("svg")
        .attr("width", windowWidth) // Ancho completo del SVG
        .attr("height", new_datas.length * barHeight + margin.top + margin.bottom) // Altura real sin restricciones
        .append("g")
        .attr("transform", `translate(${margin.left}, ${margin.top})`);

    // Eje X
    const x = d3.scaleLinear()
        .domain([0, itemWithMaxAccesses['count'] + 1])
        .range([0, width]);

    if (quantity != "all") {
        svg.append("g")
            .attr("transform", `translate(0, ${new_datas.length * barHeight})`)
            .call(d3.axisBottom(x));
    }

    // Eje Y
    const y = d3.scaleBand()
        .range([0, new_datas.length * barHeight])
        .domain(new_datas.map(d => d.title))
        .padding(0.1);

    svg.append("g")
        .call(d3.axisLeft(y));

    // Crear el tooltip
    const tooltip = d3.select("#tooltip_1")
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
    const mouseover = function(event, d) {
        tooltip
            .html(`${d.title}: ${d.count}`)
            .style("opacity", 1);
    };

    const mousemove = function(event) {
        tooltip
            .style("left", (event.pageX + 20) + "px") // Posición horizontal según el ratón
            .style("top", (event.pageY - 40) + "px"); // Posición vertical según el ratón
    };

    const mouseleave = function() {
        tooltip.style("opacity", 0);
    };

    // Dibujar barras con animación
    svg.selectAll("rect")
        .data(new_datas)
        .join("rect")
        .attr("x", x(0))
        .attr("y", d => y(d.title))
        .attr("width", 0) // Comenzar desde el ancho 0 para la animación
        .attr("height", y.bandwidth())
        .attr("fill", "#2C4C7E")
        .transition() // Transición para animar las barras
        .duration(800)
        .attr("width", d => x(d.count)); // Ampliar a su ancho final

    // Añadir interacciones para el tooltip
    svg.selectAll("rect")
        .on("mouseover", mouseover)
        .on("mousemove", mousemove)
        .on("mouseleave", mouseleave);

    if (quantity == "all") {
        const xAxisSvg = d3.select("#d3_graph_x_axis").append("svg")
            .attr("width", windowWidth)
            .attr("height", 40);

        const xAxis = xAxisSvg.append("g")
            .attr("transform", `translate(${margin.left}, 20)`)
            .call(d3.axisBottom(x));
    }
}




function drawTableResources(){
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
                    <button type="button" class='btn action-btn'>${heroicon(
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
                resource_uid = data.uid;
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
    analyticsPoaTableResources = new Tabulator("#analytics-poa-resources", {
        ajaxURL: endPointTableResources,
        ajaxConfig: "GET",
        ...tabulatorBaseConfig,
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

    controlsSearch(analyticsPoaTableResources, endPointTable, "analytics-poa-resources");
    controlsPagination(analyticsPoaTableResources, "analytics-poa-resources");
}
function fillDataResourceModal(){
    const filter_data_input = document.querySelector('#filter_date_accesses_resource');
    const filter_type_input = document.querySelector('#filter_type_resource');

    filter_data_input.addEventListener("change", function () {
        filter_date = getFlatpickrDateRangeSql(flatpickrDateFilterResource);
        if (filter_date.length > 1){
            const startDate = new Date(filter_date[0]);
            const endDate = new Date(filter_date[1]);
            const diffInMs = endDate - startDate;
            const diffInDays = diffInMs / (1000 * 60 * 60 * 24);
            const days = Math.ceil(diffInDays);
            if (days > 20){
                filter_type = 'YYYY-MM';
                document.getElementById("filter_type_resource").value = 'YYYY-MM';
            } else if (days > 365){
                filter_type = 'YYYY';
                document.getElementById("filter_type_resource").value = 'YYYY';
            }

            sendDataResources();
        }

    });

    filter_type_input.addEventListener("change", function () {
        filter_type = document.getElementById("filter_type_resource").value;
        if (filter_type != null){
            sendDataResources();
        }

    });
}
function sendDataResources(){
    const formData = new FormData();

    formData.append("filter_date_resource", filter_date);
    formData.append("filter_type_resource", filter_type);
    formData.append("educational_resource_uid", resource_uid);

    const params = {
        url: "/analytics/users/get_resources_data",
        method: "POST",
        body: formData,
        loader: true,
    };

    apiFetch(params)
        .then((data) => {
            drawGraphResource(data);
            let date = data['filter_date'].split(",");
            flatpickrDateFilterResource.setDate([convertirFechas(date[0]), convertirFechas(date[1])]);
            document.getElementById("filter_type_resource").value = data['date_format'];

        });
}
function drawGraphResource(datas) {
    document.getElementById("second_graph").innerHTML = "";

    // Limpiar el contenedor y actualizar datos adicionales
    if (datas.last_access.access_date != "" && datas.last_access.user_name != "") {
        document.getElementById("last_access_resource").innerHTML = formatDateTime(datas.last_access.access_date) + " " + datas.last_access.user_name;
    }
    if (datas.last_visit.date != "") {
        document.getElementById("last_visit_resource").innerHTML = formatDateTime(datas.last_visit.access_date);
    }
    document.getElementById("unique_users_resource").innerHTML = datas.different_users;


    // Definir márgenes para el gráfico y la leyenda
    const margin = {top: 30, right: 30, bottom: 70, left: 50},
        width = d3.select("#second_graph").node().getBoundingClientRect().width - margin.left - margin.right,
        height = 400 - margin.top - margin.bottom;

    // Crear el SVG y el contenedor gráfico
    const svg = d3.select("#second_graph")
        .append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom + 50)
        .append("g")
        .attr("transform", `translate(${margin.left},${margin.top})`);

    // Preparar los datos y definir colores
    let new_data;
    if (datas['accesses'][0].length == 0) {
        new_data = datas['visits'][0].map(item1 => ({
            group: item1.access_date_group,
            Accesos: 0,
            Visitas: item1.access_count || 0
        }));
    } else if (datas['visits'][0].length == 0) {
        new_data = datas['accesses'][0].map(item1 => ({
            group: item1.access_date_group,
            Accesos: item1.access_count || 0,
            Visitas: 0
        }));
    } else {
        new_data = datas['accesses'][0].map(item1 => {
            const item2 = datas['visits'][0].find(item => item.access_date_group === item1.access_date_group);
            return {
                group: item1.access_date_group,
                Accesos: item1.access_count || 0,
                Visitas: item2 ? item2.access_count : 0
            };
        });
    }

    new_data.columns = ["group", "Accesos", "Visitas"];
    const subgroups = ["Accesos", "Visitas"];
    const groups = new_data.map(d => d.group);

    let itemMax = datas.max_value;

    if (itemMax == 0){
        itemMax = 1;
    }

    // Ejes X y Y
    const x = d3.scaleBand().domain(groups).range([0, width]).padding([0.2]);
    const y = d3.scaleLinear().domain([0, itemMax]).range([height, 0]);
    const xSubgroup = d3.scaleBand().domain(subgroups).range([0, x.bandwidth()]).padding([0.05]);

    // Color por subgrupo
    const color = d3.scaleOrdinal().domain(subgroups).range(['#2C4C7E', '#7E2C4C']);

    // Dibujar ejes
    const xAxis = svg.append("g").attr("transform", `translate(0,${height})`).call(d3.axisBottom(x).tickSize(0));

    // Añadir líneas punteadas horizontales
    const yAxis = svg.append("g").call(d3.axisLeft(y));

    // Dibujar líneas punteadas horizontales
    const gridlines = svg.append("g")
        .attr("class", "grid")
        .selectAll("line")
        .data(y.ticks())
        .enter().append("line")
        .attr("class", "gridline")
        .attr("x1", 0)
        .attr("x2", width)
        .attr("y1", d => y(d))
        .attr("y2", d => y(d))
        .style("stroke", "#ccc")
        .style("stroke-dasharray", "2,2");

    // Rotar los textos del eje X a 45 grados
    xAxis.selectAll("text")
        .attr("transform", "rotate(-45)")
        .attr("text-anchor", "end")
        .attr("dx", "-0.5em")
        .attr("dy", "0.5em"); // Ajuste aquí para bajar los textos

    // Dibujar barras con animación y tooltip
    svg.append("g").selectAll("g")
        .data(new_data)
        .enter().append("g")
        .attr("transform", d => `translate(${x(d.group)},0)`)
        .selectAll("rect")
        .data(d => subgroups.map(key => ({key: key, value: d[key]})))
        .enter().append("rect")
        .attr("x", d => xSubgroup(d.key))
        .attr("y", height)
        .attr("width", xSubgroup.bandwidth())
        .attr("height", 0)
        .attr("fill", d => color(d.key))
        .transition()
        .duration(800)
        .attr("y", function (d) {
            return y(d.value);
        })
        .attr("height", function (d) {
            return height - y(d.value);
        });


    // Crear la leyenda con icono de información y tooltip
    const legendContainer = d3.select("#second_graph")
        .append("div")
        .style("display", "flex")
        .style("justify-content", "space-around")
        .style("position", "relative"); // Asegura que los tooltips se posicionen bien

    // Datos de la leyenda con información adicional
    const legendData = [
        { name: "Accesos", color: "#2C4C7E", info: "Número de accesos de alumnos matriculados al curso desde su perfil." },
        { name: "Visitas", color: "#7E2C4C", info: "Número de visitas realizadas por los usuarios desde fuera de su perfil." }
    ];

    // Crear los elementos de la leyenda con el ícono de información
    legendContainer.selectAll(".legend-item")
        .data(legendData)
        .enter().append("div")
        .attr("class", "legend-item")
        .style("display", "flex")
        .style("align-items", "center")
        .style("position", "relative")  // Necesario para posicionar el tooltip
        .style("margin-right", "20px")
        .html(d => `
            <div style="width: 20px; height: 20px; background-color: ${d.color}; margin-right: 5px;"></div>
            ${d.name}
            <span class="tooltip-i" style="margin-left: 8px; cursor: pointer; color: white;">${heroicon("tooltip-i", "outline")}</span>
            <div class="tooltip" style="border: 1px solid black; visibility: hidden; background-color: white; color: black; text-align: center; border-radius: 5px; padding: 5px; position: absolute; bottom: 125%; left: 50%; transform: translateX(-50%); white-space: nowrap; z-index: 1; opacity: 0; transition: opacity 0.3s;">
                ${d.info}
            </div>
        `);

    // Añadir interacción para mostrar el tooltip al pasar el ratón sobre la "i"
    legendContainer.selectAll(".legend-item")
        .on("mouseover", function() {
            d3.select(this).select(".tooltip")
                .style("visibility", "visible")
                .style("opacity", "1");
        })
        .on("mouseout", function() {
            d3.select(this).select(".tooltip")
                .style("visibility", "hidden")
                .style("opacity", "0");
        });

}
function drawTableResourcesAccesses(){
    const columns = [
        { title: "Título", field: "title", widthGrow: 8 },
        {
            title: "Fecha de primer acceso",
            field: "first_access",
            formatter: function (cell, formatterParams, onRendered) {
                const isoDate = cell.getValue();
                if (!isoDate) return "";
                return formatDateTime(isoDate);
            },
            widthGrow: 2,
        },
        {
            title: "Fecha de último acceso",
            field: "last_access",
            formatter: function (cell, formatterParams, onRendered) {
                const isoDate = cell.getValue();
                if (!isoDate) return "";
                return formatDateTime(isoDate);
            },
            widthGrow: 2,
        },
    ];
    analyticsPoaTableResourcesAccesses = new Tabulator("#analytics-poa-resources-accesses", {
        ajaxURL: endPointTableResourcesAccesses,
        ajaxConfig: "GET",
        ...tabulatorBaseConfig,
        ajaxResponse: async function (url, params, response) {
            updatePaginationInfo(
                analyticsPoaTableResourcesAccesses,
                response,
                "analytics-poa-resources-accesses"
            );
            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });

    controlsSearch(analyticsPoaTableResourcesAccesses, endPointTableResourcesAccesses, "analytics-poa-resources-accesses");
    controlsPagination(analyticsPoaTableResourcesAccesses, "analytics-poa-resourcesaccesses");
}
function drawGraphResources(){
    //get data
    let datas;
    const params = {
        url: "/analytics/users/get_poa_graph_resources",
        method: "GET"
    };
    apiFetch(params).then((data) => {
        const quantity = document.getElementById("itemsPerGraphResources").value;
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
        document.getElementById("d3_graph_resources_x_axis").classList.remove("hidden");
    } else {
        datas = allDatas.slice(0, quantity);
        document.getElementById("d3_graph_resources_x_axis").classList.add("hidden");
    }

    const new_datas = datas.map((element, index) => ({
        title: (index + 1) + " " + element.title.substring(0, 50),
        count: parseInt(element.accesses_count)
    }));

    const itemWithMaxAccesses = new_datas.reduce((max, current) =>
        current.count > max.count ? current : max
    );

    var div = document.getElementById('d3_graph_resources');
    var ancho = div.clientWidth;

    // Definir márgenes y dimensiones
    const margin = { top: 20, right: 30, bottom: 40, left: 200 },
        width = ancho - margin.left - margin.right,
        barHeight = 25,  // Altura de cada barra
        maxHeight = 400;  // Altura máxima fija cuando quantity es "all"

    // Definir la altura total dependiendo de la cantidad de datos
    let height = new_datas.length * barHeight + margin.top + margin.bottom;

    // Si quantity es "all", limitar la altura y activar el scroll
    if (quantity === "all" && height > maxHeight) {
        height = maxHeight;  // Fijar un alto máximo para el contenedor
    }

    // Seleccionar el contenedor del gráfico y ajustar el scroll si es necesario
    const container = d3.select("#d3_graph_resources")
        .style("width", `${width + margin.left + margin.right}px`)  // Ancho ajustado
        .style("height", `${height}px`)  // Altura ajustada con scroll si es necesario
        .style("overflow-y", (quantity === "all" ? "scroll" : "visible"));  // Activar scroll solo si quantity es "all"

    // Crear el SVG
    const svg = container.append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", new_datas.length * barHeight + margin.top + margin.bottom)  // Altura real sin restricciones
        .append("g")
        .attr("transform", `translate(${margin.left}, ${margin.top})`);

    // Eje X
    const x = d3.scaleLinear()
        .domain([0, itemWithMaxAccesses['count'] + 1])
        .range([0, width]);

    if (quantity !== "all") {
        // Dibujar eje X directamente en el gráfico si quantity no es "all"
        svg.append("g")
            .attr("transform", `translate(0, ${new_datas.length * barHeight})`)
            .call(d3.axisBottom(x))
            .selectAll("text")
            .attr("transform", "translate(-10,0)rotate(-45)")
            .style("text-anchor", "end");
    } else {
        // Si quantity es "all", dibujar el eje X en el contenedor separado
        const xAxisContainer = d3.select("#d3_graph_resources_x_axis")
            .append("svg")
            .attr("width", width + margin.left + margin.right)
            .attr("height", 60)  // Altura suficiente para el eje y los textos rotados
            .append("g")
            .attr("transform", `translate(${margin.left}, 20)`);  // Ajustar la posición del eje X dentro del nuevo SVG

        xAxisContainer.append("g")
            .call(d3.axisBottom(x))
            .selectAll("text")
            .attr("transform", "translate(-10,0)rotate(-45)")  // Rotar los textos del eje X
            .style("text-anchor", "end");
    }

    // Eje Y
    const y = d3.scaleBand()
        .range([0, new_datas.length * barHeight])  // Ajustar el rango Y según la cantidad de datos
        .domain(new_datas.map(d => d.title))
        .padding(0.1);

    svg.append("g")
        .call(d3.axisLeft(y));

    // Crear el tooltip
    const tooltip = d3.select("body").append("div")
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
    const mouseover = function(event, d) {
        tooltip
            .html(`Accesos: ${d.count}`)
            .style("opacity", 1);
    };

    const mousemove = function(event) {
        tooltip
            .style("left", (event.pageX + 20) + "px")  // Posición horizontal según el ratón
            .style("top", (event.pageY - 40) + "px");  // Posición vertical según el ratón
    };

    const mouseleave = function() {
        tooltip.style("opacity", 0);
    };

    // Dibujar barras con animación y tooltip
    svg.selectAll("rect")
        .data(new_datas)
        .join("rect")
        .attr("x", x(0))
        .attr("y", d => y(d.title))
        .attr("width", 0)  // Iniciar con ancho cero para la animación
        .attr("height", y.bandwidth())
        .attr("fill", "#2C4C7E")
        .on("mouseover", mouseover)
        .on("mousemove", mousemove)
        .on("mouseleave", mouseleave)
        .transition()  // Añadir transición
        .duration(1000)  // Duración de la animación
        .attr("width", d => x(d.count));  // Ancho final de las barras
}





function graficarTreeMap(allDatas) {

    const primerValor = {
        name: "origin",
        value: '', // Ajusta el valor según sea necesario
        parent: ''
    };

    const data = [primerValor, ...allDatas.map((element, index) => ({
        name: element.title,
        value: parseInt(element.accesses_count),
        parent: 'origin'
    }))];

    const color_treemap = d3.scaleOrdinal()
    .domain(data.map(d => d.name)) // Definir dominio según los nombres
    .range(['#507ab9', '#2C4C7E', '#7E5E2C', '#7E2C4C', '#2C7E5E', '#B39264']); // Lista de colores personalizados

    // Obtener el ancho del contenedor
    const container = document.getElementById("d3_graph_treemap");
    const anchoContenedor = container.clientWidth; // Ancho del contenedor
    const margin = {top: 10, right: 10, bottom: 10, left: 10};
    const width = anchoContenedor - margin.left - margin.right; // Ancho ajustado del gráfico
    const height = 445 - margin.top - margin.bottom; // Altura fija o ajustada si es necesario

    // Limpiar el contenedor
    d3.select("#d3_graph_treemap").selectAll("*").remove();

    // Append the svg object to the body of the page
    const svg = d3.select("#d3_graph_treemap")
        .append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom)
        .append("g")
        .attr("transform",
                `translate(${margin.left}, ${margin.top})`);

    // Stratify the data: reformatting for d3.js
    const root = d3.stratify()
        .id(function(d) { return d.name; })
        .parentId(function(d) { return d.parent; })
        (data);
    root.sum(function(d) { return +d.value });

    // Compute the position of each element of the hierarchy
    d3.treemap()
        .size([width, height])
        .padding(4)
        (root);

    // Define a color scale
    const color = d3.scaleOrdinal(d3.schemeCategory10); // Usar una escala de colores predefinida o personalizada

    // Use this information to add rectangles:
    svg
        .selectAll("rect")
        .data(root.leaves())
        .join("rect")
        .attr('x', function (d) { return d.x0; })
        .attr('y', function (d) { return d.y0; })
        .attr('width', function (d) { return d.x1 - d.x0; })
        .attr('height', function (d) { return d.y1 - d.y0; })
        .style("stroke", "black")
        .style("fill", function(d) { return color_treemap(d.data.name); }) // Apply the color scale here
        .on("mouseover", function(event, d) {
            const tooltip = d3.select("#tooltip");
            tooltip
                .style("display", "block")
                .html(`Curso: ${d.data.name}<br>Accesos: ${d.data.value}`)
                .style("left", (event.pageX + 40) + "px")
                .style("top", (event.pageY - 80) + "px");
        })
        .on("mouseout", function() {
            d3.select("#tooltip").style("display", "none");
        });

    // And to add the text labels
    svg
        .selectAll("text")
        .data(root.leaves())
        .join("text")
        .attr("x", function(d){
            const text = d.data.name;
            const textWidth = getTextWidth(text, "12px");
            const rectWidth = d.x1 - d.x0;
            return (rectWidth > textWidth) ? d.x0 + (rectWidth - textWidth) / 2 : -9999; // Hide text if not enough space
        })
        .attr("y", function(d){
            const text = d.data.name;
            const textHeight = getTextHeight(text, "12px");
            const rectHeight = d.y1 - d.y0;
            return (rectHeight > textHeight) ? d.y0 + (rectHeight + textHeight) / 2 : -9999; // Hide text if not enough space
        })
        .text(function(d){
            const text = d.data.name;
            const textWidth = getTextWidth(text, "12px");
            const textHeight = getTextHeight(text, "12px");
            const rectWidth = d.x1 - d.x0;
            const rectHeight = d.y1 - d.y0;
            return (rectWidth > textWidth && rectHeight > textHeight) ? text : '';
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
        return metrics.actualBoundingBoxAscent + metrics.actualBoundingBoxDescent;
    }
}

function graficarTreeMapResources(allDatas) {

    const primerValor = {
        name: "origin",
        value: '', // Ajusta el valor según sea necesario
        parent: ''
    };

    const data = [primerValor, ...allDatas.map((element, index) => ({
        name: element.title,
        value: parseInt(element.accesses_count),
        parent: 'origin'
    }))];

    const color_treemap = d3.scaleOrdinal()
    .domain(data.map(d => d.name)) // Definir dominio según los nombres
    .range(['#507ab9', '#2C4C7E', '#7E5E2C', '#7E2C4C', '#2C7E5E', '#B39264']); // Lista de colores personalizados

    // Obtener el ancho del contenedor
    const container = document.getElementById("d3_graph_treemap_resources");
    const anchoContenedor = container.clientWidth; // Ancho del contenedor
    const margin = {top: 10, right: 10, bottom: 10, left: 10};
    const width = anchoContenedor - margin.left - margin.right; // Ancho ajustado del gráfico
    const height = 445 - margin.top - margin.bottom; // Altura fija o ajustada si es necesario

    // Limpiar el contenedor
    d3.select("#d3_graph_treemap_resources").selectAll("*").remove();

    // Append the svg object to the body of the page
    const svg = d3.select("#d3_graph_treemap_resources")
        .append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom)
        .append("g")
        .attr("transform",
                `translate(${margin.left}, ${margin.top})`);

    // Stratify the data: reformatting for d3.js
    const root = d3.stratify()
        .id(function(d) { return d.name; })
        .parentId(function(d) { return d.parent; })
        (data);
    root.sum(function(d) { return +d.value });

    // Compute the position of each element of the hierarchy
    d3.treemap()
        .size([width, height])
        .padding(4)
        (root);

    // Define a color scale
    const color = d3.scaleOrdinal(d3.schemeCategory10); // Usar una escala de colores predefinida o personalizada

    // Use this information to add rectangles:
    svg
        .selectAll("rect")
        .data(root.leaves())
        .join("rect")
        .attr('x', function (d) { return d.x0; })
        .attr('y', function (d) { return d.y0; })
        .attr('width', function (d) { return d.x1 - d.x0; })
        .attr('height', function (d) { return d.y1 - d.y0; })
        .style("stroke", "black")
        .style("fill", function(d) { return color_treemap(d.data.name); }) // Apply the color scale here
        .on("mouseover", function(event, d) {
            const tooltip = d3.select("#tooltip");
            tooltip
                .style("display", "block")
                .html(`Curso: ${d.data.name}<br>Accesos: ${d.data.value}`)
                .style("left", (event.pageX + 5) + "px")
                .style("top", (event.pageY - 28) + "px");
        })
        .on("mouseout", function() {
            d3.select("#tooltip").style("display", "none");
        });

    // And to add the text labels
    svg
        .selectAll("text")
        .data(root.leaves())
        .join("text")
        .attr("x", function(d){
            const text = d.data.name;
            const textWidth = getTextWidth(text, "12px");
            const rectWidth = d.x1 - d.x0;
            return (rectWidth > textWidth) ? d.x0 + (rectWidth - textWidth) / 2 : -9999; // Hide text if not enough space
        })
        .attr("y", function(d){
            const text = d.data.name;
            const textHeight = getTextHeight(text, "12px");
            const rectHeight = d.y1 - d.y0;
            return (rectHeight > textHeight) ? d.y0 + (rectHeight + textHeight) / 2 : -9999; // Hide text if not enough space
        })
        .text(function(d){
            const text = d.data.name;
            const textWidth = getTextWidth(text, "12px");
            const textHeight = getTextHeight(text, "12px");
            const rectWidth = d.x1 - d.x0;
            const rectHeight = d.y1 - d.y0;
            return (rectWidth > textWidth && rectHeight > textHeight) ? text : '';
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
        return metrics.actualBoundingBoxAscent + metrics.actualBoundingBoxDescent;
    }
}
function convertirFechas(date){
    const regex = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/;
    const fecha = new Date(date);
    const dia = String(fecha.getDate()).padStart(2, '0'); // Obtener día con dos dígitos
    const mes = String(fecha.getMonth() + 1).padStart(2, '0'); // Obtener mes (0-11) + 1
    const anio = fecha.getFullYear(); // Obtener año
    const fechaFormateada = `${dia}-${mes}-${anio}`;
    return fechaFormateada;
}
