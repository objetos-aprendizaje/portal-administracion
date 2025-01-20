@extends('layouts.app')
@section('content')
    <div class="poa-container">

        <h2>Listado de Competencias y resultados de aprendizaje</h2>

        <div class="table-control-header">

            <div class="input-with-button control-search">
                <input class="w-full" type="text" id="search-competences-input" placeholder="Buscar...">
                <button type="button" id="clean-search" class="clear-table-btn">
                    {{ eHeroicon('x-mark', 'solid') }}
                </button>
                <button type="button" id="search-competences-btn">
                    {{ eHeroicon('magnifying-glass', 'solid') }}
                </button>
            </div>

            <div>
                <button type="button" class="btn-icon" id="btn-export-import" title="Importar/Exportar">
                    {{ eHeroicon('arrows-up-down', 'outline') }}
                </button>
                <button id="import-csv-btn" type="button" class="btn btn-icon" title="Importar marco ESCO">
                    {{ eHeroicon('folder-plus', 'outline') }}
                </button>

                <button id="new-competence-framework-btn" type="button" class="btn btn-icon" title="Añadir marco de competencias">
                    {{ eHeroicon('plus', 'outline') }}
                </button>

                <button id="btn-delete-competences" type="button" class="btn btn-icon" title="Eliminar elementos seleccionados">
                    {{ eHeroicon('trash', 'outline') }}
                </button>
            </div>

        </div>

        <div id="tree-competences-learning-results">
        </div>

    </div>

    @include('cataloging.competences_learnings_results.competence_modal')
    @include('cataloging.competences_learnings_results.competence_framework_modal')
    @include('cataloging.competences_learnings_results.learning_result_modal')
    @include('cataloging.competences.import_competence_framework')
    @include('cataloging.competences.import_esco_framework')
    @include('cataloging.competences.export_import_modal')


    @include('partials.modal-confirmation')
@endsection
