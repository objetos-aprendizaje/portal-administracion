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
                        <label for="filter_course_status_uid">Estado</label>
                    </div>
                    <div class="content-container mt-1">
                        <select id="filter_course_status_uid" name="filter_course_status_uid" class="poa-select w-full">
                            <option value="" selected></option>
                            @foreach ($courses_statuses as $course_status)
                                <option value="{{ $course_status['uid'] }}">{{ $course_status['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <div class="label-container label-center">
                        <label for="filter_call_uid">Convocatoria</label>
                    </div>
                    <div class="content-container mt-1">
                        <select id="filter_call_uid" name="filter_call_uid" class="poa-select w-full">
                            <option value="" selected>Ninguna</option>
                            @foreach ($calls as $call)
                                <option value="{{ $call['uid'] }}">{{ $call['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <div class="label-container">
                        <label for="filter_educational_program_type_uid">Tipo de programa formativo</label>
                    </div>
                    <div class="content-container mt-1">
                        <select id="filter_educational_program_type_uid" name="filter_educational_program_type_uid"
                            class="poa-select w-full">
                            <option value="" selected>Ninguno</option>
                            @foreach ($educationals_programs_types as $educational_program_type)
                                <option value="{{ $educational_program_type['uid'] }}">
                                    {{ $educational_program_type['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <div class="label-container label-center">
                        <label for="filter_course_type_uid">Tipo de curso</label>
                    </div>

                    <div class="content-container mt-1">
                        <select id="filter_course_type_uid" name="filter_course_type_uid" class="poa-select w-full">
                            <option value="" selected>Selecciona tipo de curso</option>
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
                        <input type="datetime-local" class="poa-input" id="filter_inscription_date" name="filter_inscription_date" />
                    </div>
                </div>

                <div>
                    <div class="label-container label-center">
                        <label for="filter_realization_date">Fecha Realización</label>
                    </div>
                    <div class="content-container mt-1">
                        <input type="datetime-local" class="poa-input" id="filter_realization_date" name="filter_realization_date" />
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
                            name="filter_min_ects_workload" value="" />
                    </div>
                </div>

                <div>
                    <div class="label-container">
                        <label for="filter_max_ects_workload">Máximo ECTS</label>
                    </div>

                    <div class="content-container mt-1">
                        <input type="number" class="poa-input" id="filter_max_ects_workload"
                            name="filter_max_ects_workload" value="" />
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

                <div >
                    <div class="label-container label-center">
                        <label for="filter_min_cost">Mínimo Coste (€)</label>
                    </div>
                    <div class="content-container mt-1">
                        <input type="number" placeholder="100€" class="poa-input" id="filter_min_cost"
                            name="filter_min_cost" value="" />
                    </div>
                </div>

                <div >
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
                        <label for="filter_teachers">Docentes</label>
                    </div>

                    <div class="content-container mt-1">
                        <select id="filter_teachers" class="mb-4" name="teacher[]" multiple
                            placeholder="Selecciona un docente..." autocomplete="off">
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher['uid'] }}">
                                    {{ $teacher['first_name'] }} {{ $teacher['last_name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div >
                    <div class="label-container">
                        <label for="filter_min_required_students">Mínimo de estudiantes requeridos</label>
                    </div>

                    <div class="content-container mt-1">
                        <input type="number" class="poa-input" id="filter_min_required_students"
                            name="filter_min_required_students" value="" />
                    </div>
                </div>

                <div >
                    <div class="label-container">
                        <label for="filter_max_required_students">Máximo de estudiantes requeridos</label>
                    </div>

                    <div class="content-container mt-1">
                        <input type="number" class="poa-input" id="filter_max_required_students"
                            name="filter_max_required_students" value="" />
                    </div>
                </div>

                <div>
                    <div class="label-container label-center">
                        <label for="filter_center">Centro</label>
                    </div>

                    <div class="content-container mt-1">
                        <input class="poa-input" type="text" id="filter_center"
                            name="filter_center" />
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

            </div>

            <div class="flex justify-center mt-8">
                <button id="filtrar-btn" type="button" class="btn btn-primary">
                    Filtrar {{ e_heroicon('adjustments-horizontal', 'outline') }}</button>
            </div>

        </div>

    </div>

</div>
