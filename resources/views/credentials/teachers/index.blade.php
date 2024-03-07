@extends('layouts.app')

@section('content')
    <div class="poa-container mb-8">

        <h2>Listado de docentes</h2>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'teachers-table'])
        </div>

        <div class="table-container">
            <div id="teachers-table"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'teachers-table'])

    </div>
    @include('partials.modal-confirmation')
    @include('credentials.teachers.courses_teacher_modal')

@endsection
