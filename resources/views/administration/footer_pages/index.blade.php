@extends('layouts.app')

@section('content')

    <div class="poa-container mb-8">

        <h2>Páginas footer</h2>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'footer-pages-table'])

            <div class="flex gap-1">
                <div>
                    <button type="button" class="btn-icon" id="new-footer-page-btn" title="Añadir página de footer">
                        {{ e_heroicon('plus', 'outline') }}
                    </button>
                </div>
                <div>
                    <button type="button" class="btn-icon" id="delete-footer-pages-btn" title="Eliminar página de footer">
                        {{ e_heroicon('trash', 'outline') }}
                    </button>
                </div>
                <div>
                    <button type="button" class="btn-icon" id="btn-reload-table" title="Actualizar">
                        {{ e_heroicon('arrow-path', 'outline') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div id="footer-pages-table"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'footer-pages-table'])

    </div>

    @include('administration.footer_pages.footer_page_modal')
    @include('partials.modal-confirmation')


@endsection
