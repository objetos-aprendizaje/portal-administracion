@extends('layouts.app')

@section('content')

    <div class="poa-container mb-8">
        <h2>Listado de tipos de recurso educativo</h2>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'educational-resource-types-table'])


            <div>
                <button id="add-educational-resource-type-btn" type="button" class="btn btn-icon mb-4" title="Añadir tipo de recurso educativo">{{eHeroicon('plus', 'outline')}}</button>
                <button id="delete-educational-resource-type-btn" type="button" class="btn btn-icon mb-4" title="Eliminar tipos de recursos educativos">{{eHeroicon('trash', 'outline')}}</button>
            </div>

        </div>

        <div class="table-container">
            <div id="educational-resource-types-table"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'educational-resource-types-table'])


    </div>

    @include('cataloging.educational_resource_types.educational_resource_type_modal')
    @include('partials.modal-confirmation')

@endsection
