@extends('layouts.app')

@section('content')

    <div class="poa-container mb-8">
        <h2>Listado de Notificaciones por usuarios</h2>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'notification-per-users-table'])
        </div>

        <div class="table-container">
            <div id="notification-per-users-table"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'notification-per-users-table'])


    </div>


    @include('notifications.notifications_per_users.notification_per_users_modal')


@endsection
