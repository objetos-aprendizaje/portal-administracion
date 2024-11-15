@extends('layouts.app')
@section('content')

    <div class="gap-12">
        <div class="poa-container w-full mb-8">

            <div class="table-container mt-6">
                <h2>Gráfico</h2>
                <p>
                    Este gráfico representa la distrubución de roles con respecto al total de los usuarios registrados en la plataforma.
                </p>
                <div id="d3_graph"></div>
            </div>
        </div>
        <div class="poa-container w-full mb-8">
            <div class="title-filter flex items-center mb-[26px]">
                <span>Listado de usuarios</span>
                <button id="filter-users-btn" class="btn-filter">{{ e_heroicon('adjustments-horizontal', 'outline') }}</button>
            </div>

            <div class="table-control-header">
                @include('partials.table-search', ['table' => 'analytics-students-table'])
            </div>

            <div id="filters" class="filters flex flex-wrap gap-x-3 gap-y-2 mb-4">
                <button id="delete-all-filters" class="delete-filters-btn hidden">Limpiar filtros</button>
            </div>

            <div class="table-container mt-6">
                <div id="analytics-students-table"></div>
            </div>
            @include('partials.table-pagination', ['table' => 'analytics-students-table'])
        </div>

    </div>

    @include('analytics.users_per_role.analytics-user-modal')
    @include("users.list_users.filter_users")

@endsection
