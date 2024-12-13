<div id="filter-courses-modal" class="modal">
    <div class="modal-body w-full md:max-w-[800px]">
        <div class="modal-header">
            <div>
                <h2 id="filter-courses-modal-title">Filtrar cursos</h2>
            </div>

            <div>
                <button data-modal-id="filter-courses-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>


        <div class="poa-form">

            <div class="grid md:grid-cols-2 grid-cols-1  gap-4">
                <div>
                    <div class="label-container label-center">
                        <label for="filter_courses_statuses">Estados de curso</label>
                    </div>

                    <div class="content-container mt-1">
                        <select id="filter_courses_statuses" class="mb-4" name="courses_statuses[]" multiple
                            placeholder="Selecciona estados de curso..." autocomplete="off">
                            @foreach ($courses_statuses as $course_status)
                                <option value="{{ $course_status['uid'] }}">
                                    {{ $course_status['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <div class="label-container label-center">
                        <label for="filter_calls">Convocatoria</label>
                    </div>

                    <div class="content-container mt-1">
                        <select id="filter_calls" class="mb-4" name="filter_calls[]" multiple
                            placeholder="Selecciona convocatorias..." autocomplete="off">
                            @foreach ($calls as $call)
                                <option value="{{ $call['uid'] }}">{{ $call['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <div class="label-container label-center">
                        <label for="filter_educational_program_types">Tipo de programa formativo</label>
                    </div>

                    <div class="content-container mt-1">
                        <select id="filter_educational_program_types" class="mb-4"
                            name="filter_educational_program_types[]" multiple
                            placeholder="Selecciona tipos de programas..." autocomplete="off">
                            @foreach ($educationals_programs_types as $educational_program_type)
                                <option value="{{ $educational_program_type['uid'] }}">
                                    {{ $educational_program_type['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <div class="label-container label-center">
                        <label for="filter_course_types">Tipo de curso</label>
                    </div>

                    <div class="content-container mt-1">
                        <select id="filter_course_types" class="mb-4" name="filter_course_types[]" multiple
                            placeholder="Selecciona tipos de curso..." autocomplete="off">
                            @foreach ($courses_types as $course_type)
                                <option value="{{ $course_type['uid'] }}">
                                    {{ $course_type['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <div class="label-container label-center">
                        <label for="filter_inscription_date">Fecha Inscripción</label>
                    </div>
                    <div class="content-container mt-1">
                        <input type="datetime-local" placeholder="Selecciona un rango de fechas" class="poa-input"
                            id="filter_inscription_date" name="filter_inscription_date" />
                    </div>
                </div>

                <div>
                    <div class="label-container label-center">
                        <label for="filter_realization_date">Fecha Realización</label>
                    </div>
                    <div class="content-container mt-1">
                        <input type="datetime-local" placeholder="Selecciona un rango de fechas" class="poa-input"
                            id="filter_realization_date" name="filter_realization_date" />
                    </div>
                </div>

                <div>
                    <div class="label-container">
                        <label for="filter_validate_student_registrations">Validar registros de estudiantes</label>
                    </div>
                    <div class="content-container mt-1">
                        <select id="filter_validate_student_registrations" name="filter_validate_student_registrations"
                            class="poa-select w-full">
                            <option value="" selected></option>
                            <option value="1">Sí</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                </div>

                <div>
                    <div class="label-container">
                        <label for="filter_min_ects_workload">Mínimo ECTS</label>
                    </div>

                    <div class="content-container mt-1">
                        <input type="number" class="poa-input" id="filter_min_ects_workload"
                            name="filter_min_ects_workload" placeholder="0" value="" />
                    </div>
                </div>

                <div>
                    <div class="label-container">
                        <label for="filter_max_ects_workload">Máximo ECTS</label>
                    </div>

                    <div class="content-container mt-1">
                        <input type="number" class="poa-input" id="filter_max_ects_workload"
                            name="filter_max_ects_workload" value="" placeholder="100" />
                    </div>
                </div>

                <div class="field-col ">
                    <div class="label-container label-center">
                        <label for="filter_categories">Categorías</label>
                    </div>

                    <div class="content-container mt-1">
                        <select id="filter_categories" class="mb-4" name="categories[]" multiple
                            placeholder="Selecciona categorías..." autocomplete="off">
                            @foreach ($categories as $category)
                                <option value="{{ $category['uid'] }}">
                                    {{ $category['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>

                <div>
                    <div class="label-container label-center">
                        <label for="filter_min_cost">Mínimo Coste (€)</label>
                    </div>
                    <div class="content-container mt-1">
                        <input type="number" placeholder="100€" class="poa-input" id="filter_min_cost"
                            name="filter_min_cost" value="" />
                    </div>
                </div>

                <div>
                    <div class="label-container label-center">
                        <label for="filter_max_cost">Máximo Coste (€)</label>
                    </div>
                    <div class="content-container mt-1">
                        <input type="number" placeholder="100€" class="poa-input" id="filter_max_cost"
                            name="filter_max_cost" value="" />
                    </div>
                </div>

                <div>
                    <div class="label-container label-center">
                        <label for="filter_coordinators_teachers">Docentes coordinadores</label>
                    </div>

                    <div class="content-container mt-1">
                        <select id="filter_coordinators_teachers" class="mb-4" name="teachers_coordinators[]"
                            multiple placeholder="Selecciona un docente coordinador..." autocomplete="off">
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher['uid'] }}">
                                    {{ $teacher['first_name'] }} {{ $teacher['last_name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <div class="label-container label-center">
                        <label for="filter_no_coordinators_teachers">Docentes no coordinadores</label>
                    </div>

                    <div class="content-container mt-1">
                        <select id="filter_no_coordinators_teachers" class="mb-4"
                            name="filter_no_coordinators_teachers[]" multiple
                            placeholder="Selecciona un docente no coordinador..." autocomplete="off">
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher['uid'] }}">
                                    {{ $teacher['first_name'] }} {{ $teacher['last_name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <div class="label-container">
                        <label for="filter_min_required_students">Mínimo de estudiantes requeridos</label>
                    </div>

                    <div class="content-container mt-1">
                        <input type="number" class="poa-input" id="filter_min_required_students"
                            name="filter_min_required_students" placeholder="0" value="" />
                    </div>
                </div>

                <div>
                    <div class="label-container">
                        <label for="filter_max_required_students">Máximo de estudiantes requeridos</label>
                    </div>

                    <div class="content-container mt-1">
                        <input type="number" class="poa-input" id="filter_max_required_students"
                            name="filter_max_required_students" placeholder="0" value="" />
                    </div>
                </div>

                <div>
                    <div class="label-container label-center">
                        <label for="filter_center">Centro</label>
                    </div>

                    <div class="content-container mt-1">
                        <select id="filter_center" name="filter_center" class="poa-select w-full">
                            <option value="" selected>Selecciona centro</option>
                            @foreach ($centers as $center)
                                <option value="{{ $center['uid'] }}">
                                    {{ $center['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <div class="label-container label-center">
                        <label for="filter_creators">Creadores</label>
                    </div>

                    <div class="content-container mt-1">
                        <select id="filter_creators" class="mb-4" name="creators[]" multiple
                            placeholder="Selecciona un creador..." autocomplete="off">
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher['uid'] }}">
                                    {{ $teacher['first_name'] }} {{ $teacher['last_name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <div class="label-container label-center">
                        <label for="filter_learning_results">Resultados de aprendizaje</label>
                    </div>

                    <div class="content-container mt-1">
                        <select id="filter_learning_results" class="mb-4" name="learning_results[]" multiple
                            placeholder="Selecciona resultados de aprendizaje..." autocomplete="off">
                        </select>
                    </div>
                </div>

                @if ($general_options['enabled_recommendation_module'])
                    <div>
                        <div class="label-container label-center">
                            <label for="filter_embeddings">Embeddings</label>
                        </div>

                        <div class="content-container mt-1">
                            <select id="filter_embeddings" name="filter_embeddings" class="poa-select w-full">
                                <option value="" selected></option>
                                <option value="1">Sí</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                    </div>
                @endif

            </div>

            <div class="flex justify-center mt-8">
                <button id="filter-btn" type="button" class="btn btn-primary">
                    Filtrar {{ e_heroicon('adjustments-horizontal', 'outline') }}</button>
            </div>

        </div>

    </div>

</div>
