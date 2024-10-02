@extends('layouts.app')
@section('content')

<div class="gap-12">
    <div class="poa-container w-full mb-8">

        <h2>Visitas a cursos</h2>
        <div class="table-container mt-6">
            <div id="analytics-poa"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'analytics-poa'])

        <div class="table-container mt-6">
            <h2>Gráfico</h2>
            <p>
                Este gráfico representa el TOP de los programas formativos más visitados por los alumnos.
            </p>
            <div id="d3_graph"></div>
            <div id="d3_graph_x_axis" class="hidden"></div>
            <p>
                Podemos cambiar el número de cursos mostrados en el siguiente selector:
            </p>
            <label for="itemsPerGraph">Mostrar:</label>
            <select id="itemsPerGraph" name="itemsPerGraph">
                <option value="10" selected>TOP 10</option>
                <option value="20">TOP 20</option>
                <option value="30">TOP 30</option>
                <option value="all">Todos</option>
            </select>
        </div>

        <div class="table-container mt-6">
            <h2>Gráfico</h2>
            <p>
                Este gráfico representa los accesos a los programas formativos en forma de áreas.
            </p>
            <p>
                Podemos pasar el ratón por encima de un area y veremos una leyenda con la información detallada.
            </p>
            <div id="d3_graph_treemap"></div>
            <div id="tooltip" style="position: absolute; background: white; border: 1px solid black; padding: 5px; display: none;"></div>
        </div>

    </div>
    <div class="poa-container w-full mb-8">

        <h2>Visitas a recursos Educativos</h2>
        <div class="table-container mt-6">
            <div id="analytics-poa-resources"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'analytics-poa-resources'])

        <div class="table-container mt-6">
            <h2>Gráfico</h2>
            <p>
                Este gráfico representa el TOP de los recursos formativos más visitados por los alumnos.
            </p>
            <div id="d3_graph_resources"></div>
            <div id="d3_graph_resources_x_axis" class="hidden"></div>
            <p>
                Podemos cambiar el número de cursos mostrados en el siguiente selector:
            </p>
            <label for="itemsPerGraphResources">Mostrar:</label>
            <select id="itemsPerGraphResources" name="itemsPerGraphResources">
                <option value="10" selected>TOP 10</option>
                <option value="20">TOP 20</option>
                <option value="30">TOP 30</option>
                <option value="all">Todos</option>
            </select>
        </div>

        <div class="table-container mt-6">
            <h2>Gráfico</h2>
            <p>
                Este gráfico representa los accesos a los programas formativos en forma de áreas.
            </p>
            <p>
                Podemos pasar el ratón por encima de un area y veremos una leyenda con la información detallada.
            </p>
            <div id="d3_graph_treemap_resources"></div>
            <div id="tooltip" style="position: absolute; background: white; border: 1px solid black; padding: 5px; display: none;"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'analytics-poa-resources-accesses'])

    </div>
</div>

@endsection
