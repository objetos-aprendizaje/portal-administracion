@extends('layouts.app')
@section('content')

<div class="gap-12">
    <div class="poa-container w-full mb-8">
        <!--
        <div class="table-container mt-6">
            <div id="analytics-abandoned"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'analytics-abandoned'])
    -->
        <div class="table-container mt-6">
            <h2>Gráfico</h2>
            <div id="d3_graph"></div>
        </div>
    </div>
</div>
@endsection
