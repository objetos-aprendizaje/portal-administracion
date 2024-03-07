@extends('layouts.app')

@section('content')

    <div class="poa-container mb-8">

        <h2>Páginas header</h2>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'header-pages-table'])

            <div class="flex gap-1">
                <div>
                    <button type="button" class="btn-icon" id="new-header-page-btn">
                        {{ e_heroicon('plus', 'outline') }}
                    </button>
                </div>
                <div>
                    <button type="button" class="btn-icon" id="delete-header-pages-btn">
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
            <div id="header-pages-table"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'header-pages-table'])

    </div>

    @include('administration.header_pages.header_page_modal')
    @include('partials.modal-confirmation')


@endsection
