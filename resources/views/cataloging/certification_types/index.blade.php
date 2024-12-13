@extends('layouts.app')

@section('content')

    <div class="poa-container mb-8">
        <h2>Listado de tipos de certificación</h2>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'certification-types-table'])

            <div>
                <button id="add-certification-type-btn" type="button" class="btn btn-icon mb-4" title="Añadir tipo de certificación">{{e_heroicon('plus', 'outline')}}</button>
                <button id="delete-certification-type-btn" type="button" class="btn btn-icon mb-4" title="Eliminar tipo de certificación">{{e_heroicon('trash', 'outline')}}</button>
            </div>

        </div>

        <div class="table-container">
            <div id="certification-types-table"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'certification-types-table'])


    </div>

    @include('cataloging.certification_types.certification_type_modal')
    @include('partials.modal-confirmation')

@endsection
