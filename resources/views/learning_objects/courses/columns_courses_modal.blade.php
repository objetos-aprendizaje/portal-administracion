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
                            <input type="checkbox" id="column_identifier" name="column_identifier" value="identifier"
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="column_identifier">Identificador</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="column_title" name="column_title" value="title" checked
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="column_title">Título</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="column_status_name" name="column_status_name" value="status_name"
                                checked class="mr-[11px] w-[22px] h-[22px]">
                            <label for="column_status_name">Estado</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="column_realization_start_date"
                                name="column_realization_start_date" value="realization_start_date" checked
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="column_realization_start_date">Fecha de inicio de realización</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="column_realization_finish_date"
                                name="column_realization_finish_date" value="realization_finish_date" checked
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="column_realization_finish_date">Fecha de fin de realización</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="column_calls_name" name="column_calls_name" value="calls_name"
                                class="mr-[11px] w-[22px] h-[22px]" checked>
                            <label for="column_calls_name">Convocatoria</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="column_educational_programs_name"
                                name="column_educational_programs_name" value="educational_programs_name"
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="column_educational_programs_name">Programa formativo</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="column_educational_program_types_name"
                                name="column_educational_program_types_name" value="educational_program_types_name"
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="column_educational_program_types_name">Tipo de programa formativo</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="column_course_types_name" name="course_types_name"
                                value="course_types_name" class="mr-[11px] w-[22px] h-[22px]">
                            <label for="column_course_types_name">Tipo de curso</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="column_min_required_students" name="column_min_required_students"
                                value="min_required_students" class="mr-[11px] w-[22px] h-[22px]">
                            <label for="column_min_required_students">Mínimo de estudiantes requeridos</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="column_centers_name" name="column_centers_name"
                                value="centers_name" class="mr-[11px] w-[22px] h-[22px]">
                            <label for="column_centers_name">Centro</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="column_inscription_start_date"
                                name="column_inscription_start_date" value="inscription_start_date"
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="column_inscription_start_date">Fecha de inicio de inscripción</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="column_inscription_finish_date"
                                name="column_inscription_finish_date" value="inscription_finish_date"
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="column_inscription_finish_date">Fecha de fin de inscripción</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="column_enrolling_start_date"
                                name="column_enrolling_start_date" value="enrolling_start_date"
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="column_enrolling_start_date">Fecha de inicio de matriculación</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="column_enrolling_finish_date"
                                name="column_enrolling_finish_date" value="enrolling_finish_date"
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="column_enrolling_finish_date">Fecha de fin de matriculación</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="column_calification_type" name="column_calification_type"
                                value="calification_type" class="mr-[11px] w-[22px] h-[22px]">
                            <label for="column_calification_type">Tipo de calificación</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="column_presentation_video_url"
                                name="column_presentation_video_url" value="presentation_video_url"
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="column_presentation_video_url">Video de presentación</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="column_validate_student_registrations"
                                name="column_validate_student_registrations" value="validate_student_registrations"
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="column_validate_student_registrations">Validar registro de estudiantes</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="column_ects_workload" name="column_ects_workload"
                                value="ects_workload" class="mr-[11px] w-[22px] h-[22px]">
                            <label for="column_ects_workload">Carga de trabajo ECTS</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="column_tags" name="column_tags" value="tags"
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="column_tags">Etiquetas</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="column_contact_emails" name="column_contact_emails"
                                value="contact_emails" class="mr-[11px] w-[22px] h-[22px]">
                            <label for="column_contact_emails">Emails de contacto</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="column_lsm_url" name="column_lsm_url" value="lsm_url"
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="column_lsm_url">URL de LMS</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="column_teachers_coordinate" name="column_teachers_coordinate"
                                value="teachers_coordinate" class="mr-[11px] w-[22px] h-[22px]">
                            <label for="column_teachers_coordinate">Docentes coordinadores</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="column_teachers_no_coordinate"
                                name="column_teachers_no_coordinate" value="teachers_no_coordinate"
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="column_teachers_no_coordinate">Docentes no coordinadores</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="column_categories" name="column_categories"
                                value="categories" class="mr-[11px] w-[22px] h-[22px]">
                            <label for="column_categories">Categorias</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="column_cost" name="column_cost" value="cost"
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="column_cost">Coste</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="column_featured_big_carousel"
                                name="column_featured_big_carousel" value="featured_big_carousel"
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="column_featured_big_carousel">Destacar en el carrousel grande</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="column_featured_small_carousel"
                                name="column_featured_small_carousel" value="featured_small_carousel"
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="column_featured_small_carousel">Destacar en el carrousel pequeño</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="certidigital_credential_uid"
                                name="certidigital_credential_uid" value="certidigital_credential_uid"
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="certidigital_credential_uid">Credencial de alumnos</label>
                        </div>

                        <div class="flex">
                            <input type="checkbox" id="certidigital_teacher_credential_uid"
                                name="certidigital_teacher_credential_uid" value="certidigital_teacher_credential_uid"
                                class="mr-[11px] w-[22px] h-[22px]">
                            <label for="certidigital_teacher_credential_uid">Credencial de docente</label>
                        </div>

                        @if ($general_options['enabled_recommendation_module'])
                            <div class="flex">
                                <input type="checkbox" id="column_embeddings_status" name="column_embeddings_status"
                                    value="embeddings_status" class="mr-[11px] w-[22px] h-[22px]">
                                <label for="column_embeddings_status">¿Tiene embeddings?</label>
                            </div>
                        @endif

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
