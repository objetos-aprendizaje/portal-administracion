@extends('layouts.app')

@section('content')
    <div class="poa-container mb-8">
        <h2>Listado de notificaciones por email</h2>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'notification-email-table'])


            <div>
                <button id="add-notification-email-btn" type="button"
                    class="btn btn-icon mb-4">{{ e_heroicon('plus', 'outline') }}</button>
                <button id="delete-notification-email-btn" type="button"
                    class="btn btn-icon mb-4">{{ e_heroicon('trash', 'outline') }}</button>
            </div>

        </div>

        <div class="table-container">
            <div id="notification-email-table"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'notification-email-table'])

    </div>

    @include('notifications.email.notification_email_modal')
    @include('partials.modal-confirmation')
@endsection
