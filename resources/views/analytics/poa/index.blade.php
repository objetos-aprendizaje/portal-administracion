@extends('layouts.app')
@section('content')
    <div class="poa-container w-full mb-8">
        <h2>Visitas a cursos</h2>
        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'analytics-poa'])

            <div class="flex gap-1">
                <div>
                    <button type="button" id="download-xlsx-course" class="btn-icon" title="Exportar a excel">
                        {{ e_heroicon('arrow-down-tray', 'outline') }}
                    </button>
                </div>
            </div>
        </div>
        <div class="table-container mt-6">
            <div id="analytics-poa"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'analytics-poa'])

        <div class="mt-6">
            <div class="table-container mt-12" id="tooltip_1">
                <h2>Gráfico</h2>
                <p>
                    Representa el TOP de los programas formativos más visitados por los alumnos.
                </p>
                <div id="d3_graph"></div>
                <div id="d3_graph_x_axis" class="hidden"></div>

                <div class="flex select-number-pages num-pages-selector gap-2">
                    <label for="itemsPerGraph">Mostrar:</label>
                    <select id="itemsPerGraph" class="poa-select poa-select-mini" name="itemsPerGraph">
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                        <option value="30">30</option>
                        <option value="all">Todos</option>
                    </select>
                </div>
            </div>

            <div class="table-container mt-12">
                <h2>Gráfico</h2>
                <p>
                    Representa los accesos a los programas formativos en forma de áreas.
                </p>
                <p>
                    Podemos pasar el ratón por encima de un area y veremos una leyenda con la información detallada.
                </p>
                <div id="d3_graph_treemap"></div>
                <div id="tooltip"
                    style="border-radius: 5px; position: absolute; background: white; border: 1px solid black; padding: 5px; display: none;">
                </div>
            </div>
        </div>
    </div>

    <div class="poa-container w-full">
        <h2>Visitas a recursos educativos</h2>
        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'analytics-poa-resources'])

            <div class="flex gap-1">
                <div>
                    <button type="button" id="download-xlsx-resource" class="btn-icon" title="Exportar a excel">
                        {{ e_heroicon('arrow-down-tray', 'outline') }}
                    </button>
                </div>
            </div>
        </div>
        <div class="table-container mt-6">
            <div id="analytics-poa-resources"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'analytics-poa-resources'])

        <div class="table-container mt-12">
            <h2>Gráfico</h2>
            <p>
                Representa el TOP de los recursos formativos más visitados por los alumnos.
            </p>
            <div id="d3_graph_resources"></div>
            <div id="d3_graph_resources_x_axis" class="hidden"></div>

            <div class="flex select-number-pages num-pages-selector gap-2">
                <label for="itemsPerGraphResources">Mostrar:</label>
                <select id="itemsPerGraphResources" class="poa-select poa-select-mini" name="itemsPerGraphResources">
                    <option value="10" selected>10</option>
                    <option value="20">20</option>
                    <option value="30">30</option>
                    <option value="all">Todos</option>
                </select>
            </div>
        </div>

        <div class="table-container mt-12">
            <h2>Gráfico</h2>
            <p>
                Representa los accesos a los programas formativos en forma de áreas.
            </p>
            <p>
                Podemos pasar el ratón por encima de un area y veremos una leyenda con la información
                detallada.
            </p>
            <div id="d3_graph_treemap_resources"></div>
            <div id="tooltip"
                style="border-radius: 5px; position: absolute; background: white; border: 1px solid black; padding: 5px; display: none;">
            </div>
        </div>

        @include('analytics.poa.analytics-course-modal')
        @include('analytics.poa.analytics-resource-modal')

    </div>
@endsection
