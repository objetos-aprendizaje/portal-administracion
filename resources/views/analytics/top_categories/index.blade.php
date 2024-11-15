@extends('layouts.app')
@section('content')
    <div class="gap-12">
        <div class="poa-container w-full mb-8">

            <div class="title-filter flex items-center mb-[26px]">
                <span>TOP Categorias</span>
                <button id="filter-top-categories-btn"
                    class="btn-filter">{{ e_heroicon('adjustments-horizontal', 'outline') }}</button>
            </div>

            <div class="table-control-header">
                @include('partials.table-search', ['table' => 'analytics-top'])

                <div class="flex gap-1">
                    <div>
                        <button type="button" id="download-xlsx" class="btn-icon" title="Exportar a excel">
                            {{ e_heroicon('arrow-down-tray', 'outline') }}
                        </button>
                    </div>
                </div>
            </div>

            <div id="filters" class="filters flex flex-wrap gap-x-3 gap-y-2 mb-4">
                <button id="delete-all-filters" class="delete-filters-btn hidden">Limpiar filtros</button>
            </div>

            <div class="table-container mt-6">
                <div id="analytics-top"></div>
            </div>

            @include('partials.table-pagination', ['table' => 'analytics-top'])

            <h2 class="mt-12">Gráfico</h2>

            <p>
                Este gráfico representa el número de estudiantes matrículados que hay en los cursos asociados a las
                categorias.
            </p>
            <div id="d3_graph"></div>
        </div>

    </div>

    @include('analytics.top_categories.filter_top_categories_modal')
@endsection
