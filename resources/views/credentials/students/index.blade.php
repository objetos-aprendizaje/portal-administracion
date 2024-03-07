@extends('layouts.app')

@section('content')
    <div class="poa-container mb-8">

        <h2>Listado de estudiantes</h2>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'students-table'])

        </div>

        <div class="table-container">
            <div id="students-table"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'students-table'])

    </div>
    @include('partials.modal-confirmation')
    @include('credentials.students.courses_student_modal')

@endsection
