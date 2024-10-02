import { TabulatorFull as Tabulator } from "tabulator-tables";
import {
    tabulatorBaseConfig,
    controlsPagination,
    controlsSearch,
    updatePaginationInfo,
    formatDateTime,
 } from "../tabulator_handler";
 import { apiFetch } from "../app.js";

const endPointTable = "/analytics/users/get_poa_get";
const endPointTableAccesses = "/analytics/users/get_poa_accesses";
const endPointTableResources = "/analytics/users/get_poa_resources";
const endPointTableResourcesAccesses = "/analytics/users/get_poa_resources_accesses";

let analyticsPoaTable;
let analyticsPoaAccessesTable;
let analyticsPoaTableResources;
let analyticsPoaTableResourcesAccesses;

document.addEventListener("DOMContentLoaded", function () {

    drawTable();
    drawGraph(10);
    //drawTableAccesses();

    drawTableResources();
    //drawTableResourcesAccesses();
    drawGraphResources();

    grafico_test();

    document
        .getElementById("itemsPerGraph")
        .addEventListener('change', drawGraph);

    document
        .getElementById("itemsPerGraphResources")
        .addEventListener('change', drawGraphResources);

});

function drawTable(){
    const columns = [
        { title: "Título", field: "title", widthGrow: 8 },
        {
            title: "Número de accesos",
            field: "accesses_count",
            widthGrow: 2,
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

function graficar(allDatas, quantity){

    document.getElementById("d3_graph").innerHTML = "";
    let datas;
    if (quantity == "all"){
        datas = allDatas;
        document.getElementById("d3_graph_x_axis").classList.remove("hidden");
    }else{
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
    const margin = {top: 20, right: 30, bottom: 40, left: 300},
        width = windowWidth - margin.left - margin.right - 40, // Ancho ajustado
        barHeight = 25,  // Altura de cada barra
        maxHeight = 600;  // Altura máxima fija para el contenedor con scroll

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
        .attr("width", windowWidth)  // Ancho completo del SVG
        .attr("height", new_datas.length * barHeight + margin.top + margin.bottom)  // Altura real sin restricciones
        .append("g")
        .attr("transform", `translate(${margin.left}, ${margin.top})`);

    // Eje X
    const x = d3.scaleLinear()
        .domain([0, itemWithMaxAccesses['count'] + 1])
        .range([0, width]);

    if (quantity != "all"){
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

    // Dibujar barras
    svg.selectAll("rect")
        .data(new_datas)
        .join("rect")
        .attr("x", x(0))
        .attr("y", d => y(d.title))
        .attr("width", d => x(d.count))
        .attr("height", y.bandwidth())
        .attr("fill", "#2C4C7E");

    if (quantity == "all"){
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
            title: "Número de accesos",
            field: "accesses_count",
            widthGrow: 2,
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
    if (quantity == "all") {
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
    const margin = {top: 20, right: 30, bottom: 40, left: 200},
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

    if (quantity != "all") {
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

    // Eje Y sin scroll
    const y = d3.scaleBand()
        .range([0, new_datas.length * barHeight])  // Ajustar el rango Y según la cantidad de datos
        .domain(new_datas.map(d => d.title))
        .padding(0.1);

    svg.append("g")
        .call(d3.axisLeft(y));

    // Dibujar barras
    svg.selectAll("rect")
        .data(new_datas)
        .join("rect")
        .attr("x", x(0))
        .attr("y", d => y(d.title))
        .attr("width", d => x(d.count))
        .attr("height", y.bandwidth())
        .attr("fill", "#2C4C7E");
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


