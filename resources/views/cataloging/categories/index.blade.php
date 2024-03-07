@extends('layouts.app')
@section('content')
    <div class="poa-container">

        <h2>Listado de categorías</h2>

        <div class="table-control-header">

            <div class="input-with-button control-search">
                <input class="w-full" type="text" id="search-categories-input" placeholder="Buscar...">
                <button type="button" id="search-categories-btn">
                    {{ e_heroicon('magnifying-glass', 'solid') }}
                </button>
            </div>

            <div>
                <button id="new-category-btn" type="button" class="btn btn-icon">
                    {{ e_heroicon('plus', 'outline') }}
                </button>

                <button id="btn-delete-categories" type="button" class="btn btn-icon">
                    {{ e_heroicon('trash', 'outline') }}
                </button>
            </div>

        </div>

        <div id="list-categories">
            {!! renderCategories($categories_anidated) !!}
        </div>

    </div>

    @include('cataloging.categories.category_modal')
    @include('partials.modal-confirmation')
@endsection
