@extends('layouts.app')
@section('content')

<div class="gap-12">
    <div class="poa-container w-full mb-8">

        <div class="data-card ">
            <p class="data-card-title">Usuarios registrados</p>
            <p class="data-card-total">{{$total_users}}</p>
        </div>

        <div class="table-container mt-6">
            <div id="analytics-users-table"></div>
        </div>

        <div class="table-container mt-6">
            <h2>Gráfico</h2>
            <div id="d3_graph"></div>
        </div>
    </div>
</div>

@endsection
