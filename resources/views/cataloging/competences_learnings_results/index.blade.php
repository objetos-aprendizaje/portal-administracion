@extends('layouts.app')
@section('content')
    <div class="poa-container">

        <h2>Listado de Competencias y resultados de aprendizaje</h2>

        <div class="table-control-header">

            <div class="input-with-button control-search">
                <input class="w-full" type="text" id="search-competences-input" placeholder="Buscar...">
                <button type="button" id="search-competences-btn">
                    {{ e_heroicon('magnifying-glass', 'solid') }}
                </button>
            </div>

            <div>
                <button id="import-csv-btn" type="button" class="btn btn-icon">
                    {{ e_heroicon('folder-plus', 'outline') }}
                </button>

                <button id="new-competence-btn" type="button" class="btn btn-icon">
                    {{ e_heroicon('plus', 'outline') }}
                </button>

                <button id="btn-delete-competences" type="button" class="btn btn-icon">
                    {{ e_heroicon('trash', 'outline') }}
                </button>
            </div>

        </div>

        <div id="list-competences">
            @include('cataloging.competences_learnings_results.competences', [
                'competences' => $competences_anidated,
                'first_loop' => true,
            ])
        </div>

    </div>

    @include('cataloging.competences_learnings_results.competence_modal')
    @include('cataloging.competences_learnings_results.learning_result_modal')
    @include('cataloging.competences.import_competence_framework')
    @include('cataloging.competences.import_esco_framework')


    @include('partials.modal-confirmation')
@endsection
