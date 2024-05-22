@extends('layouts.app')
@section('content')
    <div class="poa-container">
        <div class="title-filter flex items-center mb-[26px]">
            <span>Listado de recursos educativos por usuarios</span>
        </div>

        <div class="table-control-header">

            @include('partials.table-search', ['table' => 'resources-per-users-table'])

        </div>

        <div class="table-container">
            <div id="resources-per-users-table"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'resources-per-users-table'])


    </div>

    @include('learning_objects.educational_resources_per_users.educational_resources_per_users_modal')

@endsection
