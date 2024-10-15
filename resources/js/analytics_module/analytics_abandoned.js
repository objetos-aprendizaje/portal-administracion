import { TabulatorFull as Tabulator } from "tabulator-tables";
import {
    tabulatorBaseConfig,
    controlsPagination,
    controlsSearch,
    updatePaginationInfo,
} from "../tabulator_handler";
import { apiFetch } from "../app.js";
import { heroicon } from "../heroicons.js";
import * as d3 from "d3";

const endPointTable = "/analytics/users/get_abandoned";

let analyticsAbandonedTable;
let analyticsAbandonedTableFromGraph;

document.addEventListener("DOMContentLoaded", function () {
    //drawTable();
    drawGraph();
    document
        .getElementById("export-csv")
        .addEventListener("click", function () {
            analyticsAbandonedTableFromGraph.download("csv", "datos.csv");
        });
});

function drawTable() {
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
        },
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

    controlsSearch(
        analyticsAbandonedTable,
        endPointTable,
        "analytics-abandoned"
    );
    controlsPagination(analyticsAbandonedTable, "analytics-abandoned");
}
function drawGraph() {
    //get data
    let datas;
    const params = {
        url: "/analytics/users/get_abandoned_graph",
        method: "GET",
        loader: true,
    };
    apiFetch(params).then((data) => {
        graficar(data);
    });
}

function graficar(datas) {
    const new_datas = datas.map((element, index) => ({
        group: element.title.substring(0, 50) + "...",
        acepted: parseInt(
            element.enrolled_accepted_students_count - element.abandoned
        ),
        abandoned: parseInt(element.abandoned),
        abandoned_users: element.abandoned_users,
    }));

    const groups = new_datas.map((d) => d.group);

    const maxnumber1 = new_datas.reduce(
        (max, current) => (current.acepted > max.acepted ? current : max),
        { acepted: 0 }
    );

    const maxnumber2 = new_datas.reduce(
        (max, current) => (current.abandoned > max.abandoned ? current : max),
        { abandoned: 0 }
    );

    const maxnumber = maxnumber1.acepted + maxnumber2.abandoned;

    new_datas["columns"] = ["group", "acepted", "abandoned"];

    var div = document.getElementById("d3_graph");
    var ancho = div.clientWidth;

    // Establecer altura fija y habilitar scroll
    const barWidthFactor = 2;
    const fixedHeight = 600; // Altura fija
    const margin = { top: 10, right: 50, bottom: 50, left: 300 },
        width = ancho - margin.left - margin.right - 60,
        barHeight = 5; // Altura de cada barra (ajustada a la mitad)

    // Crear el contenedor del gráfico con scroll
    const container = d3
        .select("#d3_graph")
        .style("height", `${fixedHeight}px`)
        .style("overflow-y", "scroll") // Habilitar scroll vertical
        .append("div")
        .style("width", `${width + margin.left + margin.right}px`);

    // Append the SVG object to the container
    const svg = container
        .append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", 600) // Altura dinámica en función del número de datos
        .append("g")
        .attr("transform", `translate(${margin.left},${margin.top})`);

    // List of subgroups
    const subgroups = new_datas.columns.slice(1);

    // Add Y axis
    const y = d3
        .scaleBand()
        .domain(groups) // Usando "groups" correctamente
        .range([0, new_datas.length * barHeight * 5]) // Altura dinámica basada en el número de filas
        .padding([0.2]);

    svg.append("g")
        .call(d3.axisLeft(y).tickSizeOuter(0))
        .selectAll("text")
        .style("font-size", "12px"); // Ajusta el tamaño del texto en el eje Y a 12px

    // Add X axis
    const x = d3.scaleLinear().domain([0, maxnumber]).range([0, width]);

    // Renderizar el eje X en el div d3_graph_x_axis
    const xAxisContainer = d3
        .select("#d3_graph_x_axis")
        .append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", margin.bottom)
        .append("g")
        .attr("transform", `translate(${margin.left}, ${margin.bottom - 40})`); // Posicionar el eje en la parte inferior

    xAxisContainer
        .call(d3.axisBottom(x).ticks(maxnumber))
        .selectAll("text")
        .style("font-size", "12px"); // Ajusta el tamaño del texto en el eje X a 12px

    // Color palette = one color per subgroup
    const color = d3
        .scaleOrdinal()
        .domain(subgroups)
        .range(["#2C4C7E", "#7E2C4C"]);

    // Stack the data
    const stackedData = d3.stack().keys(subgroups)(new_datas);

    // ----------------
    // Create a tooltip
    // ----------------
    const tooltip = d3
        .select(".table-container")
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
        .style("top", "40%")
        .style("width", "300px");

    // Funciones para el tooltip
    const mouseover = function (event, d) {
        let subgroupName = d3.select(this.parentNode).datum().key;
        const subgroupValue = d.data[subgroupName];

        if (subgroupName == "acepted") {
            subgroupName = "Alumnos activos en el curso: " + subgroupValue;
        }
        if (subgroupName == "abandoned") {
            subgroupName =
                "Estimación de alumnos que han abandonado el curso: " +
                subgroupValue +
                ".";
        }

        tooltip.html(subgroupName).style("opacity", 1);
    };

    const mousemove = function (event, d) {
        tooltip
            .style("left", event.pageX + 10 + "px") // Posición horizontal según el ratón
            .style("top", event.pageY + 10 + "px"); // Posición vertical según el ratón
    };

    const mouseleave = function (event, d) {
        tooltip.style("opacity", 0);
    };

    const mouseclic = function (event, d) {
        if (!d.data.abandoned) return;

        let abandoned_users;
        let abandoned_users_formated = [];
        if (d.data["abandoned_users"] != undefined) {
            abandoned_users = d.data["abandoned_users"];
            let temp;
            abandoned_users.forEach((element) => {
                temp = {
                    nombre: element.first_name + " " + element.last_name,
                    email: element.email,
                };
                abandoned_users_formated.push(temp);
            });
        }
        document.getElementById("bnt-exportar-csv").classList.remove("hidden");
        document
            .getElementById("bnt-exportar-csv-title")
            .classList.remove("hidden");

        analyticsAbandonedTableFromGraph = new Tabulator(
            "#analytics-abandoned-table-from-graph",
            {
                data: abandoned_users_formated, // Los datos a mostrar en la tabla
                layout: "fitColumns", // Ajusta las columnas al tamaño del contenedor
                columns: [
                    // Define las columnas de la tabla
                    { title: "Nombre", field: "nombre" },
                    { title: "Email", field: "email" },
                ],
            }
        );
        const element = document.getElementById(
            "analytics-abandoned-table-from-graph"
        );
        const elementPosition =
            element.getBoundingClientRect().top + window.pageYOffset - 100;

        window.scrollTo({
            top: elementPosition, // Posición a la que hacer scroll
            behavior: "smooth", // Desplazamiento suave
        });
    };

    // Mostrar las barras con animación
    svg.append("g")
        .selectAll("g")
        .data(stackedData)
        .join("g")
        .attr("fill", (d) => color(d.key))
        .style("cursor", "pointer")
        .selectAll("rect")
        .data((d) => d)
        .join("rect")
        .attr("y", (d) => y(d.data.group))
        .attr("x", (d) => x(d[0]))
        .attr("height", y.bandwidth())
        .attr("width", 0) // Iniciar con ancho cero para la animación
        .on("mouseover", mouseover)
        .on("mouseleave", mouseleave)
        .on("click", mouseclic)
        .on("mousemove", mousemove)
        .transition() // Añadir transición
        .duration(1000) // Duración de la animación
        .attr("width", (d) => x(d[1]) - x(d[0])); // Ancho final de las barras

    // Añadir leyenda
    const legendContainer = d3
        .select("#d3_graph_x_axis")
        .append("div")
        .style("display", "flex")
        .style("justify-content", "space-around")
        .style("margin-top", "20px")
        .style("position", "relative"); // Asegura que los tooltips se posicionen bien;

    const legendData = [
        {
            name: "Alumnos Activos",
            color: "#2C4C7E",
            info: "Número de alumnos que acceden regularmente al curso (30 días).",
        },
        {
            name: "Alumnos Abandonados",
            color: "#7E2C4C",
            info: "Número de alumnos que por no acceder regularmente al curso (30 días), se consideran abandonos.",
        },
    ];

    legendContainer
        .selectAll(".legend-item")
        .data(legendData)
        .enter()
        .append("div")
        .attr("class", "legend-item")
        .style("display", "flex")
        .style("align-items", "center")
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

    var div = document.getElementById("d3_graph");
    var nuevoAncho = div.clientWidth * 0.99;
    div.style.width = nuevoAncho + "px";
}
