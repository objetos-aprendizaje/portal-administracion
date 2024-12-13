@extends('layouts.app')

@section('content')

    <div class="poa-container mb-8">
        <h2>Listado de tipos de notificaciones</h2>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'notification-types-table'])

            <div>
                <button id="add-notification-type-btn" type="button" class="btn btn-icon mb-4" title="Añadit tipo de notificación">{{e_heroicon('plus', 'outline')}}</button>
                <button id="delete-notification-type-btn" type="button" class="btn btn-icon mb-4" title="Eliminar tipos de notificaciones">{{e_heroicon('trash', 'outline')}}</button>
            </div>

        </div>

        <div class="table-container">
            <div id="notification-types-table"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'notification-types-table'])


    </div>

    @include('notifications.notifications_types.notification_type_modal')
    @include('partials.modal-confirmation')

@endsection
