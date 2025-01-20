@extends('layouts.app')
@section('content')
    <div class="poa-container">
        <div class="title-filter flex items-center mb-[26px]">
            <span>Listado de recursos educativos</span>
            <button id="filter-educational-resources-btn"
                class="btn-filter" title="Filtrar">{{ eHeroicon('adjustments-horizontal', 'outline') }}</button>
        </div>

        <div class="table-control-header">

            @include('partials.table-search', ['table' => 'resources-table'])

            <div class="flex gap-1">
                <div>
                    <button type="button" class="btn-icon" id="btn-add-resource" title="Añadir recurso educativo">
                        {{ eHeroicon('plus', 'outline') }}
                    </button>
                </div>

                <div>
                    <button type="button" class="btn-icon" id="btn-delete-resources" title="Eliminar recurso educativo">
                        {{ eHeroicon('trash', 'outline') }}
                    </button>
                </div>

                @if ($general_options['enabled_recommendation_module'])
                    <div>
                        <button type="button" class="btn-icon" id="btn-regenerate-embeddings" title="Regenerar embeddings">
                            {{ eHeroicon('arrow-path-rounded-square', 'outline') }}
                        </button>
                    </div>
                @endif

                @if (auth()->user()->hasAnyRole(['ADMINISTRATOR', 'MANAGEMENT']))
                    <div>
                        <button type="button" class="btn-icon" id="change-statuses-btn" title="Cambiar estados de recursos educativos">
                            {{ eHeroicon('arrows-right-left', 'outline') }}
                        </button>
                    </div>
                @endif

                <div>
                    <button type="button" class="btn-icon" id="btn-reload-table" title="Recargar listado">
                        {{ eHeroicon('arrow-path', 'outline') }}
                    </button>
                </div>
            </div>
        </div>

        <div id="filters" class="filters flex flex-wrap gap-x-3 gap-y-2 mb-4">

            <button id="delete-all-filters" class="delete-filters-btn hidden">Limpiar filtros</button>

        </div>

        <div class="table-container">
            <div id="resources-table"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'resources-table'])


    </div>

    @include('partials.modal-confirmation')
    @include('learning_objects.educational_resources.educational_resource_modal')
    @include('learning_objects.educational_resources.change_statuses_resources')
    @include('learning_objects.educational_resources.filter_educational_resources_modal')
@endsection
