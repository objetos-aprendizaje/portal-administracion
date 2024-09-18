@extends('layouts.app')
@section('content')

<div class="gap-12">
    <div class="poa-container w-full mb-8">

        <h2>Programas Formativos</h2>
        <div class="table-container mt-6">
            <div id="analytics-poa"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'analytics-poa'])

        <div class="table-container mt-6">
            <h2>Gráfico</h2>
            <div id="d3_graph"></div>
        </div>

    </div>
    <div class="poa-container w-full mb-8">

        <h2>Recursos Educativos</h2>
        <div class="table-container mt-6">
            <div id="analytics-poa-resources"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'analytics-poa-resources'])

        <div class="table-container mt-6">
            <h2>Gráfico</h2>
            <div id="d3_graph_resources"></div>
        </div>
    </div>
</div>

@endsection
