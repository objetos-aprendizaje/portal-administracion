@extends('layouts.app')
@section('content')

    <div class="poa-container w-full mb-8">

        <h2>Cursos por estado</h2>
        <p>
            Representa el número de cursos agrupado por estado.
        </p>

        <div id="d3_graph_courses"></div>
        <div id="d3_graph_courses_x_axis" class="hidden"></div>
    </div>

    <div class="poa-container w-full mb-8">
        <div class="title-filter flex items-center mb-[26px]">
            <span>Listado de cursos</span>
            <button id="filter-courses-btn" class="btn-filter">{{ eHeroicon('adjustments-horizontal', 'outline') }}</button>
        </div>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'analytics-poa'])

            <div class="flex gap-1">
                <div>
                    <button type="button" id="download-xlsx-course" class="btn-icon" title="Exportar a excel">
                        {{ eHeroicon('arrow-down-tray', 'outline') }}
                    </button>
                </div>
            </div>
        </div>

        <div id="filters" class="filters flex flex-wrap gap-x-3 gap-y-2 mb-4">
            <button id="delete-all-filters" class="delete-filters-btn hidden">Limpiar filtros</button>
        </div>

        <div class="table-container mt-6">
            <div id="analytics-poa"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'analytics-poa'])

        <div class="mt-6">
            <div class="table-container mt-12" id="tooltip_1">
                <h2>Gráfico</h2>
                <p>
                    Representa el TOP de los cursos más accedidos por los alumnos.
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
                    Representa los accesos a los cursos en forma de áreas.
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

    @include('analytics.courses.analytics-course-modal')
    @include('learning_objects.courses.filter_courses_modal')
@endsection
