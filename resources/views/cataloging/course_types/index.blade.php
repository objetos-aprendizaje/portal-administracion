@extends('layouts.app')

@section('content')

    <div class="poa-container mb-8">
        <h2>Listado de tipos de curso</h2>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'course-types-table'])

            <div>
                <button id="add-course-type-btn" type="button" class="btn btn-icon mb-4">{{e_heroicon('plus', 'outline')}}</button>
                <button id="delete-course-type-btn" type="button" class="btn btn-icon mb-4">{{e_heroicon('trash', 'outline')}}</button>
            </div>

        </div>

        <div class="table-container">
            <div id="course-types-table"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'course-types-table'])


    </div>

    @include('cataloging.course_types.course_type_modal')
    @include('partials.modal-confirmation')

@endsection
