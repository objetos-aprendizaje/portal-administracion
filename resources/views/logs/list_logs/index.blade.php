@extends('layouts.app')
@section('content')
    <div class="poa-container">

        <h2>Logs</h2>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'logs-table'])

            <div>
                <button type="button" class="btn btn-icon" id="btn-reload-table">
                    {{ e_heroicon('arrow-path', 'outline') }}
                </button>
            </div>

        </div>

        <div class="table-container">
            <div id="logs-table"></div>

            @include('partials.table-pagination', ['table' => 'logs-table'])
        </div>

    </div>
@endsection
