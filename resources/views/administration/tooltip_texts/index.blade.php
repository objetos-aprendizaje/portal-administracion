@extends('layouts.app')
@section('content')
    <div class="poa-container">

        <h2>Listado textos para tooltips</h2>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'tooltip-texts-table'])

            <div class="flex gap-1">
                <div>
                    <button type="button" id="new-tooltip-texts-btn" class="btn-icon">
                        {{ e_heroicon('plus', 'outline') }}
                    </button>
                </div>
                <div>
                    <button type="button" class="btn-icon" id="btn-delete-tooltip-texts">
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
            <div id="tooltip-texts-table"></div>
        </div>


        @include('partials.table-pagination', ['table' => 'tooltip-texts-table'])

    </div>

    @include('administration.tooltip_texts.tooltip_texts_modal')
    @include('partials.modal-confirmation')
@endsection
