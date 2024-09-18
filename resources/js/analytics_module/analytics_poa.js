import { TabulatorFull as Tabulator } from "tabulator-tables";
import {
    tabulatorBaseConfig,
    controlsPagination,
    controlsSearch,
    updatePaginationInfo
 } from "../tabulator_handler";
 import { apiFetch } from "../app.js";

const endPointTable = "/analytics/users/get_poa_get";
const endPointTableResources = "/analytics/users/get_poa_resources";

let analyticsPoaTable;
let analyticsPoaTableResources;

document.addEventListener("DOMContentLoaded", function () {

    drawTable();
    drawGraph();

    drawTableResources();
    drawGraphResources();

});

function drawTable(){
    const columns = [
        { title: "Nombre del Objeto", field: "title", widthGrow: 8 },
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
function drawGraph(){
    //get data
    let datas;
    const params = {
        url: "/analytics/users/get_poa_graph",
        method: "GET"
    };
    apiFetch(params).then((data) => {
        graficar(data);
    });
}

function graficar(datas){

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
    const windowWidth = ancho; // Ancho completo de la página
    const margin = {top: 20, right: 30, bottom: 40, left: 300},
        width = windowWidth - margin.left - margin.right - 40, // Ancho ajustado
        barHeight = 25,  // Altura de cada barra
        height = new_datas.length * barHeight + margin.top + margin.bottom; // Altura dinámica

    // Seleccionar el contenedor y ajustar su tamaño
    const container = d3.select("#d3_graph")
        .style("width", `${windowWidth}px`)  // Ancho completo de la página
        .style("height", `${height}px`); // Altura ajustada dinámicamente

    // Crear el SVG
    const svg = container.append("svg")
        .attr("width", windowWidth)  // Ancho completo del SVG
        .attr("height", height)  // Altura ajustada
        .append("g")
        .attr("transform", `translate(${margin.left}, ${margin.top})`);

    // Eje X
    const x = d3.scaleLinear()
        .domain([0, itemWithMaxAccesses['count']])
        .range([0, width]);

    svg.append("g")
        .attr("transform", `translate(0, ${new_datas.length * barHeight})`)
        .call(d3.axisBottom(x));

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
}


function drawTableResources(){
    const columns = [
        { title: "Nombre del Recurso", field: "title", widthGrow: 8 },
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
function drawGraphResources(){
    //get data
    let datas;
    const params = {
        url: "/analytics/users/get_poa_graph_resources",
        method: "GET"
    };
    apiFetch(params).then((data) => {
        graficarResources(data);
    });
}

function graficarResources(datas){

    const new_datas = datas.map((element, index) => ({
        title: (index + 1) + " " + element.title.substring(0, 50),
        count: parseInt(element.accesses_count)
    }));

    const itemWithMaxAccesses = new_datas.reduce((max, current) =>
        current.count > max.count ? current : max
    );

    console.log(new_datas);
    console.log(itemWithMaxAccesses);

    var div = document.getElementById('d3_graph_resources');
    var ancho = div.clientWidth;
    // Definir márgenes y dimensiones
    const margin = {top: 20, right: 30, bottom: 40, left: 200},
        width = ancho - margin.left - margin.right,
        barHeight = 25,  // Altura de cada barra
        height = new_datas.length * barHeight + margin.top + margin.bottom; // Altura dinámica en función de la cantidad de datos

    // Seleccionar el contenedor del gráfico
    const container = d3.select("#d3_graph_resources")
        .style("width", `${width + margin.left + margin.right}px`)  // Ancho del contenedor
        .style("height", `${height}px`); // Ajustar la altura dinámica

    // Crear el SVG
    const svg = container.append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height)  // Ajustar altura dinámica
        .append("g")
        .attr("transform", `translate(${margin.left}, ${margin.top})`);

    // Eje X
    const x = d3.scaleLinear()
        .domain([0, itemWithMaxAccesses['count']+1])
        .range([0, width]);

    svg.append("g")
        .attr("transform", `translate(0, ${new_datas.length * barHeight})`)
        .call(d3.axisBottom(x))
        .selectAll("text")
        .attr("transform", "translate(-10,0)rotate(-45)")
        .style("text-anchor", "end");

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

