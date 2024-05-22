@extends('layouts.app')

@section('content')
    <div class="poa-container mb-8">

        <div class="title-filter flex items-center mb-[26px]">
            <span>Listado de notificaciones por email</span>
            <button id="filter-notification-email-btn" class="btn-filter">{{ e_heroicon('adjustments-horizontal', 'outline') }}</button>
        </div>


        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'notification-email-table'])


            <div>
                <button id="add-notification-email-btn" type="button"
                    class="btn btn-icon mb-4">{{ e_heroicon('plus', 'outline') }}</button>
                <button id="delete-notification-email-btn" type="button"
                    class="btn btn-icon mb-4">{{ e_heroicon('trash', 'outline') }}</button>
            </div>

        </div>

        <div id="filters" class="filters flex flex-wrap gap-x-3 gap-y-2 mb-4">

            <button id="delete-all-filters" class="delete-filters-btn hidden">Limpiar filtros</button>

        </div>

        <div class="table-container">
            <div id="notification-email-table"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'notification-email-table'])

    </div>

    @include('notifications.email.notification_email_modal')
    @include('partials.modal-confirmation')
    @include('notifications.email.filter_notification_email_modal')
@endsection
