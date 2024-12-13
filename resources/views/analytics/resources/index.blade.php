@extends('layouts.app')
@section('content')
    <div class="poa-container w-full">

        <div class="title-filter flex items-center mb-[26px]">
            <span>Visitas a recursos educativos</span>
            <button id="filter-educational-resources-btn"
                class="btn-filter" title="Filtrar">{{ e_heroicon('adjustments-horizontal', 'outline') }}</button>
        </div>

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

        <div id="filters" class="filters flex flex-wrap gap-x-3 gap-y-2 mb-4">
            <button id="delete-all-filters" class="delete-filters-btn hidden">Limpiar filtros</button>
        </div>

        <div class="table-container mt-6">
            <div id="analytics-poa-resources"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'analytics-poa-resources'])

        <div class="table-container mt-12">
            <h2>Gráfico</h2>
            <p>
                Representa el TOP de los recursos formativos más accedidos por los alumnos.
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

        @include('analytics.resources.analytics-resource-modal')
        @include('learning_objects.educational_resources.filter_educational_resources_modal')
    </div>
@endsection
