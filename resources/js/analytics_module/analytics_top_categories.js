import { TabulatorFull as Tabulator } from "tabulator-tables";
import {
    tabulatorBaseConfig,
    controlsPagination,
    controlsSearch,
    updatePaginationInfo
 } from "../tabulator_handler";
import { apiFetch } from "../app.js";


const endPointTable = "/analytics/users/get_top_table";

let analyticsTopTable;

document.addEventListener("DOMContentLoaded", function () {

    drawTable();
    drawGraph();

});
function drawTable(){
    const columns = [
        { title: "Título", field: "name", widthGrow: 8 },
        {
            title: "Número de estudiantes",
            field: "student_count",
            widthGrow: 2,
        },
    ];
    analyticsTopTable = new Tabulator("#analytics-top", {
        ajaxURL: endPointTable,
        ajaxConfig: "GET",
        ...tabulatorBaseConfig,
        ajaxResponse: async function (url, params, response) {
            updatePaginationInfo(
                analyticsTopTable,
                response,
                "analytics-top"
            );
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
function drawGraph(){

    const params = {
        url: "/analytics/users/get_top_graph",
        method: "GET"
    };
    apiFetch(params).then((data) => {

        graficar(data);

    });
}

function graficar(datas){

    const data = datas.map((element, index) => ({
        category: element.name,
        value: parseInt(element.student_count)
    }));

    const itemWithMaxAccesses = data.reduce((max, current) =>
        current.value > max.value ? current : max
    );

    // Array de colores
    const colors = ['#507ab9', '#2C4C7E', '#7E5E2C', '#7E2C4C', '#2C7E5E', '#B39264'];

    // set the dimensions and margins of the graph
    const margin = {top: 30, right: 30, bottom: 120, left: 60},
        width = 500 - margin.left - margin.right,  // Fijo en 500px de ancho
        height = 500 - margin.top - margin.bottom; // Fijo en 500px de alto

    // Limpia el contenedor antes de crear el gráfico (para evitar duplicados)
    d3.select("#d3_graph").html("");

    // append the svg object to the body of the page
    const svg = d3.select("#d3_graph")
    .append("svg")
        .attr("width", width + margin.left + margin.right)  // Fijo en 500px
        .attr("height", height + margin.top + margin.bottom) // Fijo en 500px
        .attr("style", "margin: 0 auto")
    .append("g")
        .attr("transform", `translate(${margin.left},${margin.top})`);

    // Crea el tooltip
    const tooltip = d3.select("#d3_graph")
        .append("div")
        .style("opacity", 0)
        .style("position", "absolute")
        .style("background-color", "white")
        .style("border", "solid")
        .style("border-width", "1px")
        .style("border-radius", "5px")
        .style("padding", "10px")
        .style("pointer-events", "none");

    // Funciones para mostrar y ocultar el tooltip
    const showTooltip = function(event, d) {
        tooltip
            .html(`Categoría: ${d.category}<br>Estudiantes: ${d.value}`)
            .style("opacity", 1)
            .style("left", (event.pageX + 10) + "px")
            .style("top", (event.pageY - 20) + "px");
    }

    const hideTooltip = function(event, d) {
        tooltip
            .style("opacity", 0);
    }

    // X axis
    const x = d3.scaleBand()
    .range([ 0, width ])
    .domain(data.map(d => d.category))
    .padding(0.2);

    svg.append("g")
    .attr("transform", `translate(0, ${height})`)
    .call(d3.axisBottom(x))
    .selectAll("text")
        .attr("transform", "translate(-10,0)rotate(-45)")
        .style("text-anchor", "end");

    // Add Y axis
    const y = d3.scaleLinear()
    .domain([0, itemWithMaxAccesses.value])
    .range([ height, 0]);

    svg.append("g")
    .call(d3.axisLeft(y));

    // Bars
    svg.selectAll("mybar")
    .data(data)
    .join("rect")
        .attr("x", d => x(d.category))
        .attr("y", d => y(d.value))
        .attr("width", x.bandwidth())
        .attr("height", d => height - y(d.value))
        .attr("fill", (d, i) => colors[i % colors.length]) // Usa el color según el índice
        .on("mouseover", showTooltip)  // Evento para mostrar el tooltip
        .on("mousemove", showTooltip)  // Actualiza la posición del tooltip cuando se mueve el ratón
        .on("mouseout", hideTooltip);  // Evento para ocultar el tooltip
}




