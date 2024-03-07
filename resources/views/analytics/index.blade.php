@extends('layouts.app')
@section('content')

<div class="poa-container">

    <div class="data-card ">
        <p class="data-card-title">Usuarios registrados</p>
        <p class="data-card-total">{{$total_users}}</p>
    </div>

    <div class="table-container mt-6">
        <div id="analytics-users-table"></div>
    </div>
</div>

@endsection
