@extends('layouts.app')
@section('content')

<div class="gap-12">
    <div class="poa-container w-full mb-8">
        <h2>TOP Categorias</h2>
        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'analytics-top'])
        </div>
        <div class="table-container mt-6">
            <div id="analytics-top"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'analytics-top'])

        <div class="mt-6">
            <button id="download-xlsx">Descargar Excel</button>
        <div>

        <h2 class="mt-12">Gráfico</h2>
        <p>
            Este gráfico representa el número de estudiantes matrículados que hay en los cursos asociados a las categorias.
        </p>
        <div id="d3_graph"></div>
        </div>

    </div>

</div>

@endsection
