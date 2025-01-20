@extends('layouts.app')
@section('content')
    <div class="poa-container">
        <h2>Listado de programas formativos</h2>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'educational-resource-types-table'])

            <div class="flex gap-1">
                <div>
                    <button type="button" id="new-educational-program-btn" class="btn-icon" title="Añadir programa formativo">
                        {{ eHeroicon('plus', 'outline') }}
                    </button>
                </div>

                @if (Auth::user()->hasAnyRole(['MANAGEMENT']))
                    <div>
                        <button type="button" class="btn-icon" id="change-statuses-btn" title="Cambio de estado">
                            {{ eHeroicon('arrows-right-left', 'outline') }}
                        </button>
                    </div>
                @endif

                <div>
                    <button type="button" class="btn-icon" id="btn-reload-table" title="Recargar">
                        {{ eHeroicon('arrow-path', 'outline') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div id="educational-programs-table"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'educational-resource-types-table'])

    </div>

    @include('learning_objects.educational_programs.educational_program_modal')
    @include('learning_objects.educational_programs.change_statuses_educational_programs_modal')
    @include('learning_objects.educational_programs.educational_program_students_modal')
    @include('learning_objects.educational_programs.enroll_educational_program_modal')
    @include('learning_objects.educational_programs.enroll_educational_program_csv_modal')
    @include('partials.modal-confirmation')
@endsection
