@extends('layouts.app')
@section('content')
    <div class="poa-container">

        <div class="title-filter flex items-center mb-[26px]">
            <span>Logs</span>
            <button id="filter-logs-btn" class="btn-filter">{{ e_heroicon('adjustments-horizontal', 'outline') }}</button>
        </div>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'logs-table'])

            <div>
                <button type="button" class="btn btn-icon" id="btn-reload-table" title="Actualizar">
                    {{ e_heroicon('arrow-path', 'outline') }}
                </button>
            </div>

        </div>

        <div id="filters" class="filters flex flex-wrap gap-x-3 gap-y-2 mb-4">

            <button id="delete-all-filters" class="delete-filters-btn hidden">Limpiar filtros</button>

        </div>

        <div class="table-container">
            <div id="logs-table"></div>

            @include('partials.table-pagination', ['table' => 'logs-table'])
        </div>

    </div>
    @include('logs.list_logs.filter_logs_modal')
@endsection
