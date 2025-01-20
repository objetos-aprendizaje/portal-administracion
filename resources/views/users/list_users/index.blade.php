@extends('layouts.app')

@section('content')

    <div class="poa-container mb-8">
        <div class="title-filter flex items-center mb-[26px]">
            <span>Listado de usuarios</span>
            <button id="filter-users-btn" class="btn-filter">{{ eHeroicon('adjustments-horizontal', 'outline') }}</button>
        </div>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'users-table'])

            <div>
                <button id="add-user-btn" type="button" class="btn btn-icon mb-4" title="Añadir usuario">{{eHeroicon('plus', 'outline')}}</button>
                <button id="delete-user-btn" type="button" class="btn btn-icon mb-4" title="Eliminar usuarios">{{eHeroicon('trash', 'outline')}}</button>
                <button id="export-users-btn" type="button" class="btn btn-icon mb-4" title="Exportar usuarios">{{eHeroicon('arrow-down-tray', 'outline')}}</button>
            </div>

        </div>

        <div id="filters" class="filters flex flex-wrap gap-x-3 gap-y-2 mb-4">

            <button id="delete-all-filters" class="delete-filters-btn hidden">Limpiar filtros</button>

        </div>

        <div class="table-container">
            <div id="users-table"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'users-table'])


    </div>

    @include("users.list_users.filter_users")
    @include("users.list_users.user_modal")
    @include('partials.modal-confirmation')


 @endsection
