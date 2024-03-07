@extends('layouts.app')

@section('content')

    <div class="poa-container mb-8">
        <div class="title-filter flex items-center mb-[26px]">
            <span>Listado de usuarios</span>
            <button id="filter-users-btn" class="btn-filter">{{ e_heroicon('adjustments-horizontal', 'outline') }}</button>
        </div>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'users-table'])

            <div>
                <button id="add-user-btn" type="button" class="btn btn-icon mb-4">{{e_heroicon('plus', 'outline')}}</button>
                <button id="delete-user-btn" type="button" class="btn btn-icon mb-4">{{e_heroicon('trash', 'outline')}}</button>
            </div>

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
