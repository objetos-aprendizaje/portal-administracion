<div id="columns-courses-modal" class="modal">
    <div class="modal-body w-full md:max-w-[800px]">
        <div class="modal-header flex-wrap">
            <div>
                <h2 id="columns-courses-modal-title">Columnas de cursos</h2>
            </div>

            <div>
                <button data-modal-id="columns-courses-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>
            </div>
            <div style="flex-basis: 100%;" class="-top-3 relative">
                <div class="label-container label-center">
                    <label for="filter_courses_statuses">Seleccione las columnas a mostrar en la tabla</label>
                </div>
            </div>
        </div>


        <div class="poa-form">

            <div class="grid grid-cols-1 gap-4">

                <div>
                    <div class="checkbox_columns_selector content-container mt-1 flex gap-2 flex-col">
                        <div class="flex">
                            <input type="checkbox" id="title" name="opcion" value="title" checked
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="title">Título</label>
                        </div>
                        <div class="flex">
                            <input type="checkbox" id="identifier" name="opcion" value="identifier"
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="identifier">Identificador</label>
                        </div>
                        <div class="flex">
                            <input type="checkbox" id="status_name" name="status_name" value="status_name" checked
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="status_name">Estado</label>
                        </div>
                        <div class="flex">
                            <input type="checkbox" id="realization_start_date" name="realization_start_date"
                                value="realization_start_date" checked class="mr-[11px] w-[22px] h-[22px]">
                            <label for="realization_start_date">Fecha de inicio de realización</label>
                        </div>
                        <div class="flex">
                            <input type="checkbox" id="realization_finish_date" name="realization_finish_date"
                                value="realization_finish_date" checked class="mr-[11px] w-[22px] h-[22px]">
                            <label for="realization_finish_date">Fecha de fin de realización</label>
                        </div>
                        <div class="flex">
                            <input type="checkbox" id="calls_name" name="calls_name" value="calls_name"
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="calls_name">Convocatoria</label>
                        </div>
                        <div class="flex">
                            <input type="checkbox" id="educational_programs_name" name="educational_programs_name"
                                value="educational_programs_name" class="mr-[11px] w-[22px] h-[22px]">
                            <label for="educational_programs_name">Programa formativo</label>
                        </div>
                        <div class="flex">
                            <input type="checkbox" id="educational_program_types_name"
                                name="educational_program_types_name" value="educational_program_types_name"
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="educational_program_types_name">Tipo de programa formativo</label>
                        </div>
                        <div class="flex">
                            <input type="checkbox" id="course_types_name" name="course_types_name"
                                value="course_types_name" class="mr-[11px] w-[22px] h-[22px]">
                            <label for="course_types_name">Tipo de curso</label>
                        </div>
                        <div class="flex">
                            <input type="checkbox" id="min_required_students" name="min_required_students"
                                value="min_required_students" class="mr-[11px] w-[22px] h-[22px]">
                            <label for="min_required_students">Mínimo de estudiantes requeridos</label>
                        </div>
                        <div class="flex">
                            <input type="checkbox" id="centers_name" name="centers_name" value="centers_name"
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="centers_name">Centro</label>
                        </div>
                        <div class="flex">
                            <input type="checkbox" id="inscription_start_date" name="inscription_start_date"
                                value="inscription_start_date" class="mr-[11px] w-[22px] h-[22px]">
                            <label for="inscription_start_date">Fecha de inicio de inscripción</label>
                        </div>
                        <div class="flex">
                            <input type="checkbox" id="inscription_finish_date" name="inscription_finish_date"
                                value="inscription_finish_date" class="mr-[11px] w-[22px] h-[22px]">
                            <label for="inscription_finish_date">Fecha de fin de inscripción</label>
                        </div>
                        <div class="flex">
                            <input type="checkbox" id="enrolling_start_date" name="enrolling_start_date"
                                value="enrolling_start_date" class="mr-[11px] w-[22px] h-[22px]">
                            <label for="enrolling_start_date">Fecha de inicio de matriculación</label>
                        </div>
                        <div class="flex">
                            <input type="checkbox" id="enrolling_finish_date" name="enrolling_finish_date"
                                value="enrolling_finish_date" class="mr-[11px] w-[22px] h-[22px]">
                            <label for="enrolling_finish_date">Fecha de fin de matriculación</label>
                        </div>
                        <div class="flex">
                            <input type="checkbox" id="calification_type" name="calification_type"
                                value="calification_type" class="mr-[11px] w-[22px] h-[22px]">
                            <label for="calification_type">Tipo de calificación</label>
                        </div>
                        <div class="flex">
                            <input type="checkbox" id="presentation_video_url" name="presentation_video_url"
                                value="presentation_video_url" class="mr-[11px] w-[22px] h-[22px]">
                            <label for="presentation_video_url">Video de presentación</label>
                        </div>
                        <div class="flex">
                            <input type="checkbox" id="validate_student_registrations"
                                name="validate_student_registrations" value="validate_student_registrations"
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="validate_student_registrations">Validar registro de estudiantes</label>
                        </div>
                        <div class="flex">
                            <input type="checkbox" id="ects_workload" name="ects_workload" value="ects_workload"
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="ects_workload">Carga de trabajo ECTS</label>
                        </div>
                        <div class="flex">
                            <input type="checkbox" id="tags" name="tags" value="tags"
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="tags">Etiquetas</label>
                        </div>
                        <div class="flex">
                            <input type="checkbox" id="contact_emails" name="contact_emails" value="contact_emails"
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="contact_emails">Emails de contacto</label>
                        </div>
                        <div class="flex">
                            <input type="checkbox" id="lsm_url" name="lsm_url" value="lsm_url"
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="lsm_url">URL de LMS</label>
                        </div>
                        <div class="flex">
                            <input type="checkbox" id="teachers_coordinate" name="teachers_coordinate"
                                value="teachers_coordinate" class="mr-[11px] w-[22px] h-[22px]">
                            <label for="teachers_coordinate">Docentes coordinadores</label>
                        </div>
                        <div class="flex">
                            <input type="checkbox" id="teachers_no_coordinate" name="teachers_no_coordinate"
                                value="teachers_no_coordinate" class="mr-[11px] w-[22px] h-[22px]">
                            <label for="teachers_no_coordinate">Docentes no coordinadores</label>
                        </div>
                        <div class="flex">
                            <input type="checkbox" id="categories" name="categories" value="categories"
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="categories">Categorias</label>
                        </div>
                        <div class="flex">
                            <input type="checkbox" id="cost" name="cost" value="cost"
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="cost">Coste</label>
                        </div>
                        <div class="flex">
                            <input type="checkbox" id="featured_big_carousel" name="featured_big_carousel"
                                value="featured_big_carousel" class="mr-[11px] w-[22px] h-[22px]">
                            <label for="featured_big_carousel">Destacar en el carrousel grande</label>
                        </div>
                        <div class="flex">
                            <input type="checkbox" id="featured_small_carousel" name="featured_small_carousel"
                                value="featured_small_carousel" class="mr-[11px] w-[22px] h-[22px]">
                            <label for="featured_small_carousel">Destacar en el carrousel pequeño</label>
                        </div>
                    </div>
                </div>
                <div class="flex justify-center mt-8 gap-4">
                    <button data-modal-id="columns-courses-modal" type="button"
                        class="btn btn-secondary close-modal-btn">Cerrar
                        {{ e_heroicon('x-mark', 'outline') }}</button>
                </div>
            </div>

        </div>

    </div>

</div>
