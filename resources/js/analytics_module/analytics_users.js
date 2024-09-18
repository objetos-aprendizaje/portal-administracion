import { TabulatorFull as Tabulator } from "tabulator-tables";
import { tabulatorBaseConfig } from "../tabulator_handler";
import { apiFetch } from "../app.js";


const endPointTable = "/analytics/users/get_user_roles";

let analyticsUsersTable;



document.addEventListener("DOMContentLoaded", function () {

    drawTable();
    drawGraph();

});

function drawTable(){
    const columns = [
        { title: "Rol", field: "name", widthGrow: 8 },
        {
            title: "Nº usuarios registrados",
            field: "users_count",
            widthGrow: 2,
        },
    ];
    analyticsUsersTable = new Tabulator("#analytics-users-table", {
        ajaxURL: endPointTable,
        ajaxConfig: "GET",
        ...tabulatorBaseConfig,
        ajaxResponse: async function (url, params, response) {
            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });
}

function drawGraph(){
    //get data
    let datas;
    const params = {
        url: "/analytics/users/get_user_roles_graph",
        method: "GET"
    };
    apiFetch(params).then((data) => {
        graficar(data);
    });
}

function graficar(datas){

    // set the dimensions and margins of the graph
    const width = 700,
    height = 600,
    margin = 110;

    // The radius of the pieplot is half the width or half the height (smallest one). I subtract a bit of margin.
    const radius = Math.min(width, height) / 2 - margin

    // append the svg object to the div called 'my_dataviz'
    const svg = d3.select("#d3_graph")
    .append("svg")
    .attr("width", width)
    .attr("height", height)
    .attr('style', `top: -70px; position: relative; margin:0 auto`)
    .append("g")
    .attr("transform", `translate(${width/2},${height/2})`);

    // Create dummy data
    const data = {Estudiante: datas[0]['users_count'], Administrador: datas[1]['users_count'], Docente:datas[2]['users_count'], Gestor:datas[3]['users_count']}

    // set the color scale
    const color = d3.scaleOrdinal()
    .domain(["Estudiante", "Administrador", "Docente", "Gestor"])
    .range(d3.schemeDark2);

    // Compute the position of each group on the pie:
    const pie = d3.pie()
    .sort(null) // Do not sort group by size
    .value(d => d[1])
    const data_ready = pie(Object.entries(data))

    // The arc generator
    const arc = d3.arc()
    .innerRadius(radius * 0.5)         // This is the size of the donut hole
    .outerRadius(radius * 0.8)

    // Another arc that won't be drawn. Just for labels positioning
    const outerArc = d3.arc()
    .innerRadius(radius * 0.9)
    .outerRadius(radius * 0.9)

    // Build the pie chart: Basically, each part of the pie is a path that we build using the arc function.
    svg
    .selectAll('allSlices')
    .data(data_ready)
    .join('path')
    .attr('d', arc)
    .attr('fill', d => color(d.data[1]))
    .attr("stroke", "white")
    .style("stroke-width", "2px")
    .style("opacity", 0.7)

    // Add the polylines between chart and labels:
    svg
    .selectAll('allPolylines')
    .data(data_ready)
    .join('polyline')
    .attr("stroke", "black")
    .style("fill", "none")
    .attr("stroke-width", 1)
    .attr('points', function(d) {
    const posA = arc.centroid(d) // line insertion in the slice
    const posB = outerArc.centroid(d) // line break: we use the other arc generator that has been built only for that
    const posC = outerArc.centroid(d); // Label position = almost the same as posB
    const midangle = d.startAngle + (d.endAngle - d.startAngle) / 2 // we need the angle to see if the X position will be at the extreme right or extreme left
    posC[0] = radius * 0.95 * (midangle < Math.PI ? 1 : -1); // multiply by 1 or -1 to put it on the right or on the left
    return [posA, posB, posC]
    })

    // Add the polylines between chart and labels:
    svg
    .selectAll('allLabels')
    .data(data_ready)
    .join('text')
    .text( function(d){
            let sufijo = "s";
            const vocales = ['a', 'e', 'i', 'o', 'u'];
            const ultimaLetra = d.data[0][d.data[0].length - 1].toLowerCase();
            if (!vocales.includes(ultimaLetra)){
                sufijo = "es"
            }
            if (d.value > 1 || d.value == 0){
                return d.value + ' ' + d.data[0] + sufijo;
            }
            if (d.value == 1){
                return d.value + ' ' + d.data[0];
            }
        }
    )
    .attr('transform', function(d) {
        const pos = outerArc.centroid(d);
        const midangle = d.startAngle + (d.endAngle - d.startAngle) / 2
        pos[0] = radius * 0.99 * (midangle < Math.PI ? 1 : -1);
        return `translate(${pos})`;
    })
    .style('text-anchor', function(d) {
        const midangle = d.startAngle + (d.endAngle - d.startAngle) / 2
        return (midangle < Math.PI ? 'start' : 'end')
    })
}
