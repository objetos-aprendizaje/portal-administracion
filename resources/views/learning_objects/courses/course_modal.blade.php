<div id="course-modal" class="modal">
    <div class="modal-body w-full md:max-w-[1200px]">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">Añade un nuevo curso</h2>
            </div>

            <div>
                <button data-modal-id="course-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php eHeroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>

        <form id="course-form" enctype="multipart/form-data">
            @csrf

            <div class="poa-form">
                <!-- CAMPOS NO PERTENECE PROGRAMA FORMATIVO -->
                <div class="field mt-2">
                    <div class="label-container label-center">
                        <label for="belongs_to_educational_program">¿Pertenece a un programa
                            formativo?</label>
                    </div>
                    <div class="content-container mt-1">
                        <div class="checkbox">
                            <label for="belongs_to_educational_program"
                                class="inline-flex relative items-center cursor-pointer">
                                <input type="checkbox" id="belongs_to_educational_program"
                                    name="belongs_to_educational_program" class="sr-only peer">
                                <div
                                    class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Contenedor del acordeón -->
                <div class="accordion" id="accordion-container">

                    <!-- Primer elemento del acordeón -->
                    <div class="accordion-element border-b">
                        <button type="button"
                            class="accordion-header">
                            <span>Información general del curso</span>
                            <span class="accordion-icon">+</span>
                        </button>

                        <div class="accordion-content hidden px-4 py-2">

                            <div id="field-created-by" class="hidden">
                                <div class="field">
                                    <div class="label-container label-center">
                                        <label for="created-by">Creado por</label>
                                    </div>
                                    <div id="created-by"></div>
                                </div>
                            </div>

                            <div id="field-has-embeddings" class="hidden">
                                <div class="field">
                                    <div class="label-container label-center">
                                        <label for="has-embeddings">Embeddings generados</label>
                                    </div>
                                    <div id="has-embeddings"></div>
                                </div>
                            </div>


                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="title">Título <span class="text-danger">*</span></label>
                                </div>
                                <div class="content-container mt-1">
                                    <input maxlength="255" placeholder="Curso en bellasartes" class="poa-input"
                                        type="text" id="title" name="title" />
                                </div>
                            </div>

                            <div class="field mt-2">
                                <div class="label-container">
                                    <label for="description">Descripción</label>
                                </div>
                                <div class="content-container mt-1">
                                    <textarea maxlength="1000" placeholder="Los contenidos del curso son los siguientes..." rows="5" class="poa-input"
                                        id="description" name="description"></textarea>
                                </div>
                            </div>

                            <div class="field mt-2">
                                <div class="label-container">
                                    <label for="objectives">Objetivos</label>
                                </div>
                                <div class="content-container mt-1">
                                    <textarea maxlength="1000" placeholder="Los objetivos del curso son los siguientes..." rows="5" class="poa-input"
                                        id="objectives" name="objectives"></textarea>
                                </div>
                            </div>

                            <div class="field mt-2">
                                <div class="label-container label-center">
                                    <label for="center_uid">Centro</label>
                                </div>

                                <div class="content-container mt-1">
                                    <select id="center_uid" name="center_uid" class="poa-select w-full">
                                        <option value="" selected>Selecciona centro</option>
                                        @foreach ($centers as $center)
                                            <option value="{{ $center['uid'] }}">
                                                {{ $center['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            @if ($general_options['operation_by_calls'])
                                <div class="field mt-2">
                                    <div class="label-container label-center">
                                        <label for="call_uid">Convocatoria <span class="text-danger">*</span></label>
                                    </div>
                                    <div class="content-container mt-1">
                                        <div class="select-container">
                                            <select id="call_uid" name="call_uid" class="poa-select w-full">
                                                <option value="" selected>Selecciona una convocatoria</option>
                                                @foreach ($calls as $call)
                                                    <option value="{{ $call['uid'] }}">{{ $call['name'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="field mt-2">
                                <div class="label-container label-center">
                                    <label for="teachers-coordinators">Docentes coordinadores</label>
                                </div>
                                <div class="content-container mt-1" id="teachers-container">
                                    <select id="teachers-coordinators" class="mb-4" name="teacher_coordinators[]"
                                        multiple placeholder="Selecciona un docente coordinador..."
                                        autocomplete="off">
                                        @foreach ($teachers as $teacher)
                                            <option value="{{ $teacher['uid'] }}">
                                                {{ $teacher['first_name'] }} {{ $teacher['last_name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="field mt-2">
                                <div class="label-container label-center">
                                    <label for="teachers-no-coordinators">Docentes no coordinadores</label>
                                </div>
                                <div class="content-container mt-1" id="teachers-container">
                                    <select id="teachers-no-coordinators" class="mb-4"
                                        name="teacher_no_coordinators[]" multiple
                                        placeholder="Selecciona un docente no coordinador..." autocomplete="off">
                                        @foreach ($teachers as $teacher)
                                            <option value="{{ $teacher['uid'] }}">
                                                {{ $teacher['first_name'] }} {{ $teacher['last_name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="field mt-2">
                                <div class="label-container">
                                    <label for="contact_information">Información de contacto</label>
                                </div>
                                <div class="content-container mt-1">
                                    <textarea maxlength="1000" placeholder="Información de contacto..." rows="5" class="poa-input"
                                        id="contact_information" name="contact_information"></textarea>
                                </div>
                            </div>

                            <div class="field mt-2">
                                <div class="label-container label-center">
                                    <label for="course_type_uid">Tipo de curso <span
                                            class="text-danger">*</span></label>
                                </div>

                                <div class="content-container mt-1">
                                    <div class="select-container">
                                        <select id="course_type_uid" name="course_type_uid"
                                            class="poa-select w-full">
                                            <option value="" selected>Selecciona tipo de curso</option>
                                            @foreach ($courses_types as $course_type)
                                                <option value="{{ $course_type['uid'] }}">
                                                    {{ $course_type['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="field mt-2">
                                <div class="label-container label-center">
                                    <label for="contact_emails">Emails de contacto</label>
                                </div>

                                <div class="content-container mt-1">
                                    <input id="contact_emails" autocomplete="off" name="contact_emails"
                                        placeholder="Introduce emails de contacto" />
                                </div>
                            </div>

                            <div class="field mt-2">
                                <div class="label-container">
                                    <label for="image_input_file">Imagen</label>
                                </div>
                                <div class="content-container mt-1">
                                    <div class="poa-input-image">
                                        <img id="image_path_preview" src="{{ env('NO_IMAGE_SELECTED_PATH') }}" alt="preview imagen" />
                                        <span class="dimensions">*Dimensiones: Alto: 50px x Ancho: 300px. Formato: PNG,
                                            JPG. Tam.
                                            Máx.: 1MB</span>
                                        <div class="select-file-container">
                                            <input accept="image/*" type="file" id="image_input_file"
                                                name="image_input_file" class="hidden" />
                                            <div class="flex items-center gap-[20px]">
                                                <label for="image_input_file" class="btn btn-rectangular">
                                                    Subir {{ eHeroicon('arrow-up-tray', 'outline') }}
                                                </label>
                                                <span class="image-name text-[14px]">Ningún archivo seleccionado</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="field mt-2" data-id="presentation_video_url">
                                <div class="label-container label-center">
                                    <label for="presentation_video_url">URL Vídeo de presentación</label>
                                </div>
                                <div class="content-container mt-1">
                                    <input placeholder="https://youtube.com/" class="poa-input" type="text"
                                        id="presentation_video_url" name="presentation_video_url" />
                                </div>
                            </div>

                            <div class="field mt-2">
                                <div class="label-container label-center">
                                    <label for="certification_type_uid">Tipo de certificación</label>
                                </div>
                                <div class="content-container mt-1">
                                    <div class="select-container">
                                        <select id="certification_type_uid" name="certification_type_uid"
                                            class="poa-select w-full">
                                            <option value="" selected>Ninguno</option>
                                            @foreach ($certificationTypes as $certificationType)
                                                <option value="{{ $certificationType['uid'] }}">
                                                    {{ $certificationType['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="field mt-2">
                                <div class="label-container label-center">
                                    <label for="ects_workload">Carga de trabajo ECTS</label>
                                </div>

                                <div class="content-container mt-1">
                                    <input type="number" class="poa-input" id="ects_workload" name="ects_workload"
                                        value="0" />
                                </div>
                            </div>

                            <div class="field mt-2">
                                <div class="label-container label-center">
                                    <label for="realization_start_date">Fecha de inicio de realización <span
                                            class="text-danger">*</span></label>
                                </div>
                                <div class="content-container mt-1">
                                    <input type="datetime-local" class="poa-input" id="realization_start_date"
                                        name="realization_start_date" />
                                </div>
                            </div>

                            <div class="field mt-2">
                                <div class="label-container label-center">
                                    <label for="realization_finish_date">Fecha de fin de realización <span
                                            class="text-danger">*</span></label>
                                </div>
                                <div class="content-container mt-1">
                                    <input type="datetime-local" class="poa-input" id="realization_finish_date"
                                        name="realization_finish_date" />
                                </div>
                            </div>

                            <section id="tags-container">
                                <div class="field mt-2">
                                    <div class="label-container label-center">
                                        <label for="tags">Etiquetas</label>
                                    </div>

                                    <div class="content-container mt-1" id="tags-container">
                                        <input id="tags" name="tags" autocomplete="off" name="tags"
                                            placeholder="Introduce etiquetas" />

                                        @if ($general_options['openai_key'])
                                            <a href="javascript:void(0)" id="generate-tags-btn">Generar etiquetas</a>
                                        @endif
                                    </div>
                                </div>
                            </section>

                            <section id="categories-container">
                                <div class="field mt-2">
                                    <div class="label-container label-center">
                                        <label for="select-categories">Categorías</label>
                                    </div>

                                    <div class="content-container mt-1" id="categories-container">
                                        <select id="select-categories" class="mb-4" name="categories[]" multiple
                                            placeholder="Selecciona categorías..." autocomplete="off">
                                            @foreach ($categories as $category)
                                                <option value="{{ $category['uid'] }}">
                                                    {{ $category['name'] }}</option>
                                            @endforeach
                                        </select>

                                        <small id="categories-median-inscriptions">Seleccione una o varias categorías
                                            para calcular
                                            la mediana de inscripciones</small>
                                    </div>
                                </div>
                            </section>

                            <div class="field mt-2">
                                <div class="label-container label-center">
                                    <label for="lms_system_uid">LMS</label>
                                </div>
                                <div class="content-container mt-1">
                                    <select class="poa-select w-full" id="lms_system_uid" name="lms_system_uid">
                                        <option value="" selected>Selecciona LMS</option>
                                        @foreach ($lmsSystems as $lms)
                                            <option value="{{ $lms->uid }}">
                                                {{ $lms->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="field mt-2">
                                <div class="label-container label-center">
                                    <label for="lms_url">URL LMS</label>
                                </div>
                                <div class="content-container mt-1">
                                    <input class="poa-input" placeholder="moodle url" type="text" id="lms_url"
                                        name="lms_url" />
                                </div>
                            </div>

                            <section id="min-required-students-container">
                                <div class="field mt-2">
                                    <div class="label-container label-center">
                                        <label for="min_required_students">Mínimo de estudiantes requeridos</label>
                                    </div>

                                    <div class="content-container mt-1">
                                        <input type="number" class="poa-input" id="min_required_students"
                                            name="min_required_students" value="0" />
                                    </div>
                                </div>
                            </section>


                        </div>
                    </div>

                    <!-- Segundo elemento del acordeón -->
                    <div class="border-b" id="element-accordion-inscription-enrollment">
                        <button type="button"
                            class="accordion-header w-full text-left px-4 py-2 flex justify-between items-center focus:outline-none hover:bg-gray-50">
                            <span class="font-semibold">Inscripción/Matriculación</span>
                            <span class="accordion-icon">+</span>
                        </button>
                        <div class="accordion-content hidden px-4 py-2">
                            <section id="inscription-dates-container">
                                <div class="field mt-2">
                                    <div class="label-container label-center">
                                        <label for="inscription_start_date">Fecha de inicio de inscripción <span
                                                class="text-danger">*</span></label>
                                    </div>
                                    <div class="content-container mt-1">
                                        <input type="datetime-local" class="poa-input" id="inscription_start_date"
                                            name="inscription_start_date" />
                                    </div>
                                </div>

                                <div class="field mt-2">
                                    <div class="label-container label-center">
                                        <label for="inscription_finish_date">Fecha de fin de inscripción <span
                                                class="text-danger">*</span></label>
                                    </div>
                                    <div class="content-container mt-1">
                                        <input type="datetime-local" class="poa-input" id="inscription_finish_date"
                                            name="inscription_finish_date" />
                                    </div>
                                </div>
                            </section>

                            <section id="validate-student-registrations-container">
                                <div class="field mt-2">
                                    <div class="label-container label-center">
                                        <label for="validate_student_registrations">Validar registros de
                                            estudiantes</label>
                                    </div>
                                    <div class="content-container mt-1">
                                        <div class="checkbox">
                                            <label for="validate_student_registrations"
                                                class="inline-flex relative items-center cursor-pointer">
                                                <input type="checkbox" id="validate_student_registrations"
                                                    name="validate_student_registrations" class="sr-only peer">
                                                <div
                                                    class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="hidden" id="criteria-area">
                                <div class="field mt-2">
                                    <div class="label-container">
                                        <label for="evaluation_criteria">Criterio de validación <span
                                                class="text-danger">*</span></label>
                                    </div>

                                    <div class="content-container mt-1">
                                        <textarea maxlength="1000" placeholder="Los criterios de evaluación son los siguientes..." rows="5"
                                            class="poa-input" id="evaluation_criteria" name="evaluation_criteria"></textarea>
                                    </div>
                                </div>
                            </section>

                            <div class="hidden mt-2" id="validation-information-field">
                                <div class="label-container label-center">
                                    <label for="validation_information">Información de validación</label>
                                </div>

                                <div class="content-container">
                                    <textarea maxlength="1000" placeholder="Los estudiantes deberán cumplir los siguientes requisitos..." rows="5"
                                        class="poa-input" name="validation_information" id="validation_information"></textarea>
                                </div>
                            </div>

                            <section class="hidden" id="documents-container">
                                <div class="field">
                                    <div class="label-container">
                                        <label for="document-list">Documentos necesarios para la inscripción</label>
                                    </div>

                                    <div class="content-container" id="document-container">
                                        <div class="document-list" id="document-list">

                                        </div>

                                        <div class="flex justify-end">
                                            <div>
                                                <button type="button" class="btn-icon" id="btn-add-document">
                                                    {{ eHeroicon('plus', 'outline') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section id="cost-container">
                                <div class="field mt-2">
                                    <div class="label-container label-center">
                                        <label for="payment_mode">Forma de pago (€)</label>
                                    </div>
                                    <div class="content-container mt-1">
                                        <select class="poa-select w-full" id="payment_mode" name="payment_mode">
                                            <option value="SINGLE_PAYMENT">Pago único</option>
                                            <option value="INSTALLMENT_PAYMENT">Pago fraccionado</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="field mt-2">
                                    <div class="label-container" id="label-container-cost">
                                        <label for="cost">Coste (€)</label>
                                    </div>
                                    <div class="content-container mt-1">
                                        <input type="number" placeholder="100€" step="any" class="poa-input"
                                            id="cost" name="cost" value="" />

                                        <div id="payment_terms" class="hidden">
                                            <div id="payment-terms-list"></div>

                                            <div class="flex justify-end">
                                                <div>
                                                    <button type="button" class="btn-icon" id="btn-add-payment">
                                                        {{ eHeroicon('plus', 'outline') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="hidden" id="enrolling-dates-container">
                                <div class="field mt-2">
                                    <div class="label-container label-center">
                                        <label for="enrolling_start_date">Fecha de inicio de matriculación <span
                                                class="text-danger">*</span></label>
                                    </div>
                                    <div class="content-container mt-1">
                                        <input type="datetime-local" class="poa-input" id="enrolling_start_date"
                                            name="enrolling_start_date" />
                                    </div>
                                </div>

                                <div class="field mt-2">
                                    <div class="label-container label-center">
                                        <label for="enrolling_finish_date">Fecha de fin de matriculación <span
                                                class="text-danger">*</span></label>
                                    </div>
                                    <div class="content-container mt-1">
                                        <input type="datetime-local" class="poa-input" id="enrolling_finish_date"
                                            name="enrolling_finish_date" />
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>

                    <!-- Tercer elemento del acordeón -->
                    <div class="border-b" id="element-accordion-configuration-portal-web">
                        <button type="button"
                            class="accordion-header w-full text-left px-4 py-2 flex justify-between items-center focus:outline-none hover:bg-gray-50">
                            <span class="font-semibold">Configuración en el portal web</span>
                            <span class="accordion-icon">+</span>
                        </button>
                        <div class="accordion-content hidden px-4 py-2">
                            <section id="feature-main-slider-container">
                                <div class="field mt-2">
                                    <div class="label-container label-center">
                                        <label for="featured_big_carrousel">Destacar en el slider principal</label>
                                    </div>

                                    <div class="content-container mt-1">
                                        <div class="checkbox">
                                            <label for="featured_big_carrousel"
                                                class="inline-flex relative items-center cursor-pointer">
                                                <input type="checkbox" id="featured_big_carrousel"
                                                    name="featured_big_carrousel" class="sr-only peer">
                                                <div
                                                    class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="hidden" id="big-carrousel-info">
                                    <div class="field mt-2">
                                        <div class="label-container label-center">
                                            <label for="featured_big_carrousel_title">Título en el slider
                                                principal</label>
                                        </div>
                                        <div class="content-container mt-1">
                                            <input maxlength="255" type="text" placeholder="Curso en bellasartes"
                                                class="poa-input" id="featured_big_carrousel_title"
                                                name="featured_big_carrousel_title" />
                                        </div>
                                    </div>

                                    <div class="field mt-2">
                                        <div class="label-container">
                                            <label for="featured_big_carrousel_description">Descripción en el slider
                                                principal</label>
                                        </div>

                                        <div class="content-container mt-1">
                                            <textarea maxlength="1000"
                                                placeholder="La aportación más significativa del grado en bellas artes es la de formar artistas capaces de aportar criterios..."
                                                rows="5" class="poa-input" id="featured_big_carrousel_description"
                                                name="featured_big_carrousel_description"></textarea>
                                        </div>
                                    </div>

                                    <div class="field mt-2">
                                        <div class="label-container">
                                            <label for="featured_slider_color_font">Color de la tipografía</label>
                                        </div>

                                        <div class="content-container mt-1">
                                            <div class="coloris-button">
                                                <input value="" id="featured_slider_color_font"
                                                    name="featured_slider_color_font" class="coloris" type="text"
                                                    data-coloris>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="field mt-2">
                                        <div class="label-container">
                                            <label for="featured_big_carrousel_image_path">Imagen en el slider
                                                grande</label>
                                        </div>
                                        <div class="content-container mt-1">
                                            <div class="poa-input-image mb-2">
                                                <img id="featured_big_carrousel_image_path_preview"
                                                    src="{{ env('NO_IMAGE_SELECTED_PATH') }}" alt="imagen destacada de slider grande" />

                                                <div class="select-file-container">
                                                    <input accept="image/*" type="file"
                                                        id="featured_big_carrousel_image_path"
                                                        name="featured_big_carrousel_image_path" class="hidden" />

                                                    <div class="flex items-center gap-[20px]">
                                                        <label for="featured_big_carrousel_image_path"
                                                            class="btn btn-rectangular">
                                                            Subir {{ eHeroicon('arrow-up-tray', 'outline') }}
                                                        </label>

                                                        <span class="image-name text-[14px]">Ningún archivo
                                                            seleccionado</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <a id="previsualize-slider" href="javascript:void(0)">Previsualizar
                                                slider</a>
                                        </div>
                                    </div>

                                </div>

                            </section>

                            <section id="feature-main-carrousel-container">
                                <div class="field mt-2">
                                    <div class="label-container label-center">
                                        <label for="featured_small_carrousel">Destacar en el carrousel
                                            principal</label>
                                    </div>

                                    <div class="content-container mt-1">
                                        <div class="checkbox">
                                            <label for="featured_small_carrousel"
                                                class="inline-flex relative items-center cursor-pointer">
                                                <input type="checkbox" id="featured_small_carrousel"
                                                    name="featured_small_carrousel" class="sr-only peer">
                                                <div
                                                    class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>

                    <!-- Cuarto elemento del acordeón -->
                    <div>
                        <button type="button"
                            class="accordion-header w-full text-left px-4 py-2 flex justify-between items-center focus:outline-none hover:bg-gray-50">
                            <span class="font-semibold">Bloques y elementos</span>
                            <span class="accordion-icon">+</span>
                        </button>
                        <div class="accordion-content hidden px-4 py-2">
                            <div class="mt-2 max-h-[400px] overflow-auto" id="course-composition-block">

                                <div id="course-composition" class="space-y-4">

                                </div>
                                <button type="button" id="addBlock" class="btn btn-primary mt-4">Añadir Bloque
                                    {{ eHeroicon('plus', 'outline') }}</button>

                                <template id="block-template">
                                    <div class="composition block p-4 border rounded" data-uid="" data-order="">
                                        <div class="flex flex-col">
                                            <button type="button" class="removeBlock btn-remove">Eliminar bloque
                                                {{ eHeroicon('trash', 'outline') }}</button>

                                            <select class="block-type border-full border-x">
                                                <option disabled value="">Tipo de bloque</option>
                                                <option value="THEORETIC">Teórico</option>
                                                <option value="PRACTICAL">Práctico</option>
                                                <option value="EVALUATION">Evaluación</option>
                                            </select>
                                            <input type="text" class="block-name border-full border"
                                                placeholder="Nombre del bloque">

                                            <textarea maxlength="1000" class="block-description border-full " placeholder="Descripción del bloque"></textarea>

                                            <div class="block-competences border-primary border-x p-[15px] rounded-b border-b  max-h-[]"
                                                data-order="">
                                                <input type="text" class="search-tree" data-order=""
                                                    placeholder="Escribe aquí para filtrar..." />
                                                <div class="competences-section"></div>

                                                <p class="selected-nodes">Has seleccionado <span
                                                        class="selected-nodes-count">0</span> resultados de aprendizaje
                                                    de
                                                    100</p>

                                            </div>

                                        </div>
                                        <div class="sub-blocks ml-4"></div>
                                        <button type="button" class="addSubBlock btn btn-primary mt-4">Añadir
                                            Sub-Bloque
                                            {{ eHeroicon('plus', 'outline') }}</button>
                                    </div>
                                </template>

                                <template id="sub-block-template">
                                    <div class="composition sub-block p-4 border rounded mt-2" data-subblock-uid=""
                                        data-order="">
                                        <div class="flex flex-col">

                                            <button type="button"
                                                class="removeSubBlock btn-composition btn-remove">Eliminar
                                                Sub-bloque
                                                {{ eHeroicon('trash', 'outline') }}</button>

                                            <input type="text"
                                                class="input-field p-2 rounded border sub-block-name"
                                                placeholder="Nombre del Sub-Bloque">
                                            <textarea maxlength="1000" class="textarea-field p-2 rounded border sub-block-description"
                                                placeholder="Descripción del sub-bloque"></textarea>
                                        </div>
                                        <div class="elements ml-4"></div>
                                        <button type="button" class="addElement btn btn-primary mt-4">Añadir elemento
                                            {{ eHeroicon('plus', 'outline') }}</button>
                                    </div>
                                </template>

                                <template id="element-template">
                                    <div class="composition element p-4 border rounded mt-2" data-uid=""
                                        data-order="">
                                        <div class="flex flex-col">

                                            <button type="button" class="removeElement btn-remove">Eliminar Elemento
                                                {{ eHeroicon('trash', 'outline') }}</button>

                                            <input type="text" class="input-field p-2 rounded border element-name"
                                                placeholder="Nombre del elemento">
                                            <textarea maxlength="1000" class="textarea-field p-2 rounded border element-description"
                                                placeholder="Descripción del elemento"></textarea>
                                        </div>
                                        <div class="sub-elements ml-4"></div>

                                        <button type="button" class="addSubElement btn btn-primary mt-4">Añadir
                                            Sub-Elemento
                                            {{ eHeroicon('plus', 'outline') }}</button>
                                    </div>
                                </template>

                                <template id="sub-element-template">
                                    <div class="composition sub-element p-4 border rounded mt-2" data-uid=""
                                        data-order="">
                                        <div class="flex flex-col">
                                            <button type="button" class="removeSubElement btn-remove">Eliminar
                                                Sub-Elemento
                                                {{ eHeroicon('trash', 'outline') }}</button>

                                            <input type="text"
                                                class="input-field p-2 rounded border sub-element-name"
                                                placeholder="Nombre Sub-Elemento">
                                            <textarea maxlength="1000" class="textarea-field p-2 rounded border sub-element-description"
                                                placeholder="Descripción del Sub-Elemento"></textarea>
                                        </div>
                                    </div>
                                </template>

                            </div>

                        </div>
                    </div>
                </div>






                <input type="hidden" id="course_uid" name="course_uid" value="" />

                <div class="flex justify-center mt-8 gap-4" id="btns-save">

                    <div id="draft-button-container" class="hidden">
                        <button type="submit" value="draft" id="draft-button" class="btn btn-secondary">
                            Guardar como borrador {{ eHeroicon('check', 'outline') }}</button>
                    </div>

                    <button type="submit" value="submit" id="submit-button" class="btn btn-primary">
                        Guardar {{ eHeroicon('paper-airplane', 'outline') }}</button>
                </div>

            </div>

        </form>


    </div>

</div>

<template id="document-template">
    <div class="document" data-document-uid="">
        <div class="flex gap-2 mb-2">
            <input type="text" class="poa-input document-name" placeholder="Nombre" />
            <div class="flex-none">
                <button type="button" class="btn-icon btn-remove-document">
                    {{ eHeroicon('trash', 'outline') }}
                </button>
            </div>
        </div>
    </div>
</template>

<template id="payment-term-template">
    <div class="payment-term mb-2">

        <div class="mb-2">
            <input type="text" class="poa-input payment-term-name" placeholder="Información del plazo" />
        </div>

        <div class="flex gap-2">
            <div class="flex-grow">
                <input type="datetime-local" class="poa-input w-full payment-term-start-date" name="payment_date" />
            </div>
            <div class="flex-grow">
                <input type="datetime-local" class="poa-input w-full payment-term-finish-date" name="payment_date" />
            </div>
            <div class="flex-grow">
                <input type="number" placeholder="100€" step="any" class="poa-input w-full payment-term-cost"
                    value="" />
            </div>

            <button class="w-[32px] h-[32px] btn-icon btn-remove-payment-term" type="button">
                {{ eHeroicon('trash', 'outline') }}
            </button>
        </div>
        <input type="hidden" class="payment-term-uid" name="payment_term_uid" />
    </div>
</template>
