@extends('layouts.app')
@section('content')
    <div class="poa-container">

        <h2>Listado de licencias</h2>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'licenses-table'])

            <div class="flex gap-1">
                <div>
                    <button type="button" id="new-license-btn" class="btn-icon">
                        {{ e_heroicon('plus', 'outline') }}
                    </button>
                </div>
                <div>
                    <button type="button" class="btn-icon" id="btn-delete-license">
                        {{ e_heroicon('trash', 'outline') }}
                    </button>
                </div>
                <div>
                    <button type="button" class="btn-icon" id="btn-reload-table">
                        {{ e_heroicon('arrow-path', 'outline') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div id="licenses-table"></div>
        </div>


        @include('partials.table-pagination', ['table' => 'licenses-table'])

    </div>

    @include('administration.licenses.license_modal')
    @include('partials.modal-confirmation')
@endsection
