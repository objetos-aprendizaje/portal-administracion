import { TabulatorFull as Tabulator } from "tabulator-tables";
import {
    tabulatorBaseConfig,
    controlsPagination,
    controlsSearch,
    updatePaginationInfo
 } from "../tabulator_handler";
 import { apiFetch } from "../app.js";

const endPointTable = "/analytics/users/get_abandoned";

let analyticsAbandonedTable;
let analyticsAbandonedTableFromGraph;

document.addEventListener("DOMContentLoaded", function () {

    //drawTable();
    drawGraph();
    document.getElementById("export-csv").addEventListener("click", function() {
        analyticsAbandonedTableFromGraph.download("csv", "datos.csv");
    });

});

function drawTable(){
    const columns = [
        { title: "Nombre del Curso", field: "title", widthGrow: 8 },
        {
            title: "Número de alumnos registrados",
            field: "enrolled_accepted_students_count",
            widthGrow: 2,
        },
        {
            title: "Número de abandonos (30 días sin acceder)",
            field: "abandoned",
            widthgrow: 2,
        }
    ];
    analyticsAbandonedTable = new Tabulator("#analytics-abandoned", {
        ajaxURL: endPointTable,
        ajaxConfig: "GET",
        ...tabulatorBaseConfig,
        ajaxResponse: async function (url, params, response) {
            updatePaginationInfo(
                analyticsAbandonedTable,
                response,
                "analytics-abandoned"
            );
            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });

    controlsSearch(analyticsAbandonedTable, endPointTable, "analytics-abandoned");
    controlsPagination(analyticsAbandonedTable, "analytics-abandoned");
}
function drawGraph(){
    //get data
    let datas;
    const params = {
        url: "/analytics/users/get_abandoned_graph",
        method: "GET"
    };
    apiFetch(params).then((data) => {
        graficar(data);
    });
}

function graficar(datas) {
    const new_datas = datas.map((element, index) => ({
        group: element.title.substring(0, 50) + "...",
        acepted: parseInt(element.enrolled_accepted_students_count),
        abandoned: parseInt(element.abandoned),
        abandoned_users: element.abandoned_users
    }));

    const groups = new_datas.map(d => d.group);

    const maxnumber1 = new_datas.reduce((max, current) =>
        current.acepted > max.acepted ? current : max, { acepted: 0 }
    );

    const maxnumber2 = new_datas.reduce((max, current) =>
        current.abandoned > max.abandoned ? current : max, { abandoned: 0 }
    );

    const maxnumber = maxnumber1.acepted + maxnumber2.abandoned;

    new_datas['columns'] = ['group', 'acepted', 'abandoned'];

    var div = document.getElementById('d3_graph');
    var ancho = div.clientWidth;

    // Establecer altura fija y habilitar scroll
    const fixedHeight = 600;  // Altura fija
    const margin = { top: 10, right: 50, bottom: 50, left: 300 },
        width = ancho - margin.left - margin.right,
        barHeight = 10;  // Altura de cada barra

    // Crear el contenedor del gráfico con scroll
    const container = d3.select("#d3_graph")
        .style("height", `${fixedHeight}px`)
        .style("overflow-y", "scroll")  // Habilitar scroll vertical
        .append("div")
        .style("width", `${width + margin.left + margin.right}px`);

    // append the svg object to the container
    const svg = container
        .append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", new_datas.length * barHeight + margin.top + margin.bottom)  // Altura dinámica en función del número de datos
        .append("g")
        .attr("transform", `translate(${margin.left},${margin.top})`);

    // List of subgroups = header of the csv files = soil condition here
    const subgroups = new_datas.columns.slice(1);

    // Add Y axis
    const y = d3.scaleBand()
        .domain(groups)  // Usando "groups" correctamente
        .range([0, new_datas.length * barHeight])  // Altura dinámica basada en el número de filas
        .padding([0.2]);

    svg.append("g")
        .call(d3.axisLeft(y).tickSizeOuter(0));

    // Add X axis
    const x = d3.scaleLinear()
        .domain([0, maxnumber])
        .range([0, width]);

 // Renderizar el eje X en el div d3_graph_x_axis
    const xAxisContainer = d3.select("#d3_graph_x_axis")
        .append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", margin.bottom)
        .append("g")
        .attr("transform", `translate(${margin.left}, ${margin.bottom - 20})`) // Posicionar el eje en la parte inferior

    xAxisContainer.call(d3.axisBottom(x).ticks(maxnumber));

    // Color palette = one color per subgroup
    const color = d3.scaleOrdinal()
        .domain(subgroups)
        .range(['#2C4C7E', '#7E2C4C']);

    // Stack the data --> stack per subgroup
    const stackedData = d3.stack()
        .keys(subgroups)
        (new_datas);

    // ----------------
    // Create a tooltip
    // ----------------
    const tooltip = d3.select(".table-container")
        .append("div")
        .style("opacity", 0)
        .attr("class", "tooltip")
        .style("background-color", "white")
        .style("border", "solid")
        .style("border-width", "1px")
        .style("border-radius", "5px")
        .style("padding", "10px")
        .style("position", "absolute")
        .style("right", "10%")
        .style("top", "40%");

    // Three function that change the tooltip when user hover / move / leave a cell
    const mouseover = function(event, d) {
        let subgroupName = d3.select(this.parentNode).datum().key;
        const subgroupValue = d.data[subgroupName];

        if (subgroupName == "acepted") {
            subgroupName = "Alumnos aceptados en el curso: " + subgroupValue;
        }
        if (subgroupName == "abandoned") {
            subgroupName = "Estimación de alumnos que han abandonado el curso: " + subgroupValue + ".";
        }

        tooltip
            .html(subgroupName)
            .style("opacity", 1);
    };

    const mouseleave = function(event, d) {
        tooltip.style("opacity", 0);
    };

    const mouseclic = function(event, d) {
        if (d["0"] == 1) {
            let abandoned_users;
            let abandoned_users_formated = [];
            if (d.data['abandoned_users'] != undefined) {
                abandoned_users = d.data['abandoned_users'];
                let temp;
                abandoned_users.forEach(element => {
                    temp = {
                        nombre: element.first_name + " " + element.last_name,
                        email: element.email
                    };
                    abandoned_users_formated.push(temp);
                });
            }
            document.getElementById("bnt-exportar-csv").classList.remove("hidden");
            document.getElementById("bnt-exportar-csv-title").classList.remove("hidden");

            analyticsAbandonedTableFromGraph = new Tabulator("#analytics-abandoned-table-from-graph", {
                data: abandoned_users_formated, // Los datos a mostrar en la tabla
                layout: "fitColumns", // Ajusta las columnas al tamaño del contenedor
                columns: [ // Define las columnas de la tabla
                    { title: "Nombre", field: "nombre" },
                    { title: "Email", field: "email" }
                ]
            });
        }
    };

    // Show the bars
    svg.append("g")
        .selectAll("g")
        .data(stackedData)
        .join("g")
        .attr("fill", d => color(d.key))
        .style("cursor", function(d) {
            if (d.key == "abandoned") {
                return "pointer";
            }
        })
        .selectAll("rect")
        .data(d => d)
        .join("rect")
        .attr("y", d => y(d.data.group))
        .attr("x", d => x(d[0]))
        .attr("height", y.bandwidth())
        .attr("width", d => x(d[1]) - x(d[0]))
        .on("mouseover", mouseover)
        .on("mouseleave", mouseleave)
        .on("click", mouseclic);
}
