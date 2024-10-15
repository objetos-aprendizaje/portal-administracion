@extends('layouts.app')
@section('content')

    <div class="gap-12">
        <div class="poa-container w-full mb-8">
<!--
            <div class="data-card ">
                <p class="data-card-title">Usuarios registrados</p>
                <p class="data-card-total">{{$total_users}}</p>
            </div>

            <div class="table-container mt-6">
                <div id="analytics-users-table"></div>
            </div>
        -->
            <div class="table-container mt-6">
                <h2>Gráfico</h2>
                <p>
                    Este gráfico representa la distrubución de roles con respecto al total de los usuarios registrados en la plataforma.
                </p>
                <div id="d3_graph"></div>
            </div>
        </div>
        <div class="poa-container w-full mb-8">
            <h2>Estudiantes</h2>
            <div class="table-control-header">
                @include('partials.table-search', ['table' => 'analytics-students-table'])
            </div>
            <div class="table-container mt-6">
                <div id="analytics-students-table"></div>
            </div>
            @include('partials.table-pagination', ['table' => 'analytics-students-table'])
        </div>

    </div>

    @include('analytics.users_per_role.analytics-user-modal')

@endsection
