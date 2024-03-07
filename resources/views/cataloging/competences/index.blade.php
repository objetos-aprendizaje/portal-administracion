@extends('layouts.app')
@section('content')
    <div class="poa-container">

        <h2>Listado de Competencias</h2>

        <div class="table-control-header">

            <div class="input-with-button control-search">
                <input class="w-full" type="text" id="search-competences-input" placeholder="Buscar...">
                <button type="button" id="search-competences-btn">
                    {{ e_heroicon('magnifying-glass', 'solid') }}
                </button>
            </div>

            <div>
                <button id="new-competence-btn" type="button" class="btn btn-icon">
                    {{ e_heroicon('plus', 'outline') }}
                </button>

                <button id="btn-delete-competences" type="button" class="btn btn-icon">
                    {{ e_heroicon('trash', 'outline') }}
                </button>
            </div>

        </div>

        <div id="list-competences">
            {!! renderCompetences($competences_anidated) !!}
        </div>

    </div>

    @include('cataloging.competences.competence_modal')
    @include('partials.modal-confirmation')
@endsection
