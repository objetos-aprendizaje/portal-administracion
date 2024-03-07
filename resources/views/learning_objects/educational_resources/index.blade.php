@extends('layouts.app')
@section('content')
    <div class="poa-container">
        <h2>Listado de recursos educativos</h2>

        <div class="table-control-header">

            @include('partials.table-search', ['table' => 'resources-table'])

            <div class="flex gap-2">
                <div>
                    <button type="button" class="btn-icon" id="btn-add-resource">
                        {{ e_heroicon('plus', 'outline') }}
                    </button>
                </div>

                <div>
                    <button type="button" class="btn-icon" id="btn-delete-resources">
                        {{ e_heroicon('trash', 'outline') }}
                    </button>
                </div>
                <div>
                    <button type="button" class="btn-icon" id="btn-reload-table">
                        {{ e_heroicon('arrow-path', 'outline') }}
                    </button>
                </div>

                <div>
                    <button type="button" class="btn-icon" id="change-statuses-btn">
                        {{ e_heroicon('arrows-right-left', 'outline') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div id="resources-table"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'resources-table'])


    </div>

    @include('partials.modal-confirmation')
    @include('learning_objects.educational_resources.educational_resource_modal')
    @include('learning_objects.educational_resources.change_statuses_resources')

@endsection
