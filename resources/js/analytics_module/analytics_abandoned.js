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


document.addEventListener("DOMContentLoaded", function () {

    //drawTable();
    drawGraph();


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
    // set the dimensions and margins of the graph
    const margin = {top: 10, right:50, bottom: 50, left: 300},
        width = ancho - margin.left - margin.right,
        barHeight = 20,  // Altura de cada barra
        height = new_datas.length * barHeight;  // Altura dinámica en función del número de datos

    // Elimina la altura visible fija y el scroll
    // Crear el contenedor sin scroll
    const container = d3.select("#d3_graph")
        .append("div")
        .style("width", `${width + margin.left + margin.right}px`);

    // append the svg object to the container
    const svg = container
        .append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom)
        .append("g")
        .attr("transform", `translate(${margin.left},${margin.top})`);

    // List of subgroups = header of the csv files = soil condition here
    const subgroups = new_datas.columns.slice(1);

    // Add Y axis
    const y = d3.scaleBand()
        .domain(groups)  // Usando "groups" correctamente
        .range([0, height])  // Altura dinámica basada en el número de filas
        .padding([0.2]);

    svg.append("g")
        .call(d3.axisLeft(y).tickSizeOuter(0));

    // Add X axis
    const x = d3.scaleLinear()
        .domain([0, maxnumber])
        .range([0, width]);

    svg.append("g")
        .attr("transform", `translate(0, ${height})`)
        .call(d3.axisBottom(x)
            .ticks(maxnumber)
        );

    // Color palette = one color per subgroup
    const color = d3.scaleOrdinal()
        .domain(subgroups)
        .range(['#4daf4a','#e41a1c']);

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
        .style("top", "40%")

    // Three function that change the tooltip when user hover / move / leave a cell
    const mouseover = function(event, d) {
        let subgroupName = d3.select(this.parentNode).datum().key;
        const subgroupValue = d.data[subgroupName];

        let abandoned_users;
        let abandoned_users_formated = "";
        if (d.data['abandoned_users'] != undefined){
            abandoned_users = d.data['abandoned_users'];

            abandoned_users.forEach(element => {
                abandoned_users_formated += "Nombre: " + element.first_name + " " + element.last_name + ". Email: " + element.email + "<br>";
            });
        }

        if (subgroupName == "acepted" ){
            subgroupName = "Alumnos aceptados en el curso: " + subgroupValue;
        }
        if (subgroupName == "abandoned" ){
            subgroupName = "Estimación de alumnos que han abandonado el curso: " + subgroupValue + ".<br>" + abandoned_users_formated;
        }

        tooltip
            .html(subgroupName)
            .style("opacity", 1);
    }

    const mouseleave = function(event, d) {
        tooltip.style("opacity", 0);
    }

    // Show the bars
    svg.append("g")
        .selectAll("g")
        .data(stackedData)
        .join("g")
        .attr("fill", d => color(d.key))
        .selectAll("rect")
        .data(d => d)
        .join("rect")
        .attr("y", d => y(d.data.group))
        .attr("x", d => x(d[0]))
        .attr("height", y.bandwidth())
        .attr("width", d => x(d[1]) - x(d[0]))
        .on("mouseover", mouseover)
        .on("mouseleave", mouseleave);
}
