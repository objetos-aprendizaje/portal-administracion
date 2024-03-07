@extends('layouts.app')

@section('content')

    <div class="poa-container mb-8">

        <div class="title-filter flex items-center mb-[26px]">
            <span>Listado de notificaciones generales</span>
            <button id="filter-general-notification-btn" class="btn-filter">{{ e_heroicon('adjustments-horizontal', 'outline') }}</button>
        </div>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'notification-general-table'])

            <div>
                <button id="add-notification-general-btn" type="button" class="btn btn-icon mb-4">{{e_heroicon('plus', 'outline')}}</button>
                <button id="delete-notification-general-btn" type="button" class="btn btn-icon mb-4">{{e_heroicon('trash', 'outline')}}</button>
            </div>

        </div>

        <div id="filters" class="filters flex flex-wrap gap-x-3 gap-y-2 mb-4"></div>

        <div class="table-container">
            <div id="notification-general-table"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'notification-general-table'])


    </div>

    @include('notifications.general.filter_general_notifications')
    @include('notifications.general.notification_general_modal')
    @include('notifications.general.list_users_views_general_notification')
    @include('partials.modal-confirmation')

@endsection
