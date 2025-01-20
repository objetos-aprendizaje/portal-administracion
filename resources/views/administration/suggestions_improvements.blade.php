@extends('layouts.app')

@section('content')
    <div class="poa-container mb-8">
        <h2>Envío de sugerencias y mejoras</h2>

        <p class="mb-2">Define una lista de emails donde se enviarán las sugerencias y mejoras que rellenen los usuarios
            desde el front.
        </p>

        <div class="flex justify-between mb-2 items-center ">
            <div class="flex gap-4 flex-1 items-center">
                <div class="w-1/2">
                    <input type="email" placeholder="Introduce un email" class="poa-input w-full h-full" id="email-input">
                </div>
                <div>
                    <button type="button" id="add-email-btn" class="btn-icon" title="Añadir email">
                        {{ eHeroicon('plus', 'outline') }}
                    </button>
                </div>
            </div>
            <div class="flex gap-1">
                <div>
                    <button type="button" class="btn-icon" id="btn-delete-emails" title="Eliminar emails seleccionados">
                        {{ eHeroicon('trash', 'outline') }}
                    </button>
                </div>
                <div>
                    <button type="button" class="btn-icon" id="btn-update-table" title="Actualizar">
                        {{ eHeroicon('arrow-path', 'outline') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div id="list-emails">

            </div>
        </div>

        @include('partials.table-pagination', ['table' => 'list-emails'])


    </div>

    @include('partials.modal-confirmation')
@endsection
