@extends('layouts.app')

@section('content')

    <div class="poa-container mb-8">
        <h2>Listado de tipos de programa educativo</h2>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'educational-program-types-table'])


            <div>
                <button id="add-educational-program-type-btn" type="button" class="btn btn-icon mb-4">{{e_heroicon('plus', 'outline')}}</button>
                <button id="delete-educational-program-type-btn" type="button" class="btn btn-icon mb-4">{{e_heroicon('trash', 'outline')}}</button>
            </div>

        </div>

        <div class="table-container">
            <div id="educational-program-types-table"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'educational-program-types-table'])


    </div>

    @include('cataloging.educational_program_types.educational_program_type_modal')
    @include('partials.modal-confirmation')

@endsection
