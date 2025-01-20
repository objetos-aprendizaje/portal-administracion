@extends('layouts.app')

@section('content')
    <div class="poa-container mb-8">
        <div class="title-filter flex items-center mb-[26px]">
            <span>Listado de cursos</span>
            <button id="filter-courses-btn" class="btn-filter">{{ eHeroicon('adjustments-horizontal', 'outline') }}</button>
        </div>


        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'courses-table'])

            <div class="flex gap-1">
                <div>
                    <button type="button" id="add-course-btn" class="btn-icon" title="Crear curso">
                        {{ eHeroicon('plus', 'outline') }}
                    </button>
                </div>

                @if (Auth::user()->hasAnyRole(['ADMINISTRATOR', 'MANAGEMENT']))
                    <div>
                        <button type="button" class="btn-icon" id="change-statuses-btn" title="Cambiar estado de cursos">
                            {{ eHeroicon('arrows-right-left', 'outline') }}
                        </button>
                    </div>
                @endif

                @if ($general_options['enabled_recommendation_module'])
                    <div>
                        <button type="button" class="btn-icon" id="btn-regenerate-embeddings" title="Regenerar embeddings">
                            {{ eHeroicon('arrow-path-rounded-square', 'outline') }}
                        </button>
                    </div>
                @endif

                <div>
                    <button type="button" class="btn-icon" id="btn-reload-table" title="Actualizar tabla">
                        {{ eHeroicon('arrow-path', 'outline') }}
                    </button>
                </div>

            </div>
        </div>

        <div id="filters" class="filters flex flex-wrap gap-x-3 gap-y-2 mb-4">

            <button id="delete-all-filters" class="delete-filters-btn hidden">Limpiar filtros</button>

        </div>


        <div class="table-container">
            <div id="courses-table"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'courses-table'])


    </div>

    @include('learning_objects.courses.course_modal')
    @include('learning_objects.courses.course_students_modal')
    @include('learning_objects.courses.change_statuses_courses')
    @include('learning_objects.courses.filter_courses_modal')
    @include('learning_objects.courses.columns_courses_modal')
    @include('learning_objects.courses.enroll_course_modal')
    @include('learning_objects.courses.enroll_course_csv_modal')
    @include('learning_objects.courses.califications_course_modal')
    @include('partials.modal-confirmation')
@endsection
