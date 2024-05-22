<div id="course-modal" class="modal">
    <div class="modal-body w-full md:max-w-[1200px]">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">Añade un nuevo curso</h2>
            </div>

            <div>
                <button data-modal-id="course-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>

        <form id="course-form" enctype="multipart/form-data">
            @csrf

            <div class="poa-form">

                <div id="field-created-by" class="hidden">
                    <div class="field">
                        <div class="label-container label-center">
                            <label for="created-by">Creado por</label>
                        </div>
                        <div id="created-by"></div>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="title">Título <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container mt-1">
                        <input placeholder="Curso en bellasartes" class="poa-input" type="text" id="title"
                            name="title" />
                    </div>
                </div>

                <div class="field mt-2">
                    <div class="label-container">
                        <label for="description">Descripción</label>
                    </div>
                    <div class="content-container mt-1">
                        <textarea placeholder="Los contenidos del curso son los siguientes..." rows="5" class="poa-input" id="description"
                            name="description"></textarea>
                    </div>
                </div>

                <div class="field mt-2">
                    <div class="label-container">
                        <label for="contact_information">Información de contacto</label>
                    </div>
                    <div class="content-container mt-1">
                        <textarea placeholder="Información de contacto..." rows="5" class="poa-input" id="contact_information"
                            name="contact_information"></textarea>
                    </div>
                </div>

                <div id="call-field">
                    <div class="field mt-2">
                        <div class="label-container label-center">
                            <label for="call_uid">Convocatoria <span class="text-danger">*</span></label>
                        </div>
                        <div class="content-container mt-1">
                            <div class="select-container">
                                <select id="call_uid" name="call_uid" class="poa-select w-full">
                                    <option value="" selected>Ninguna</option>
                                    @foreach ($calls as $call)
                                        <option value="{{ $call['uid'] }}">{{ $call['name'] }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="call_uid" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="field mt-2">
                    <div class="label-container label-center">
                        <label for="educational_program_uid">Programa formativo <span
                                class="text-danger">*</span></label>
                    </div>
                    <div class="content-container mt-1">
                        <div class="select-container">
                            <select id="educational_program_uid" name="educational_program_uid"
                                class="poa-select w-full">
                                <option value="" selected>Ninguno</option>
                                @foreach ($educational_programs as $educational_program)
                                    <option data-is_modular="{{ $educational_program['is_modular'] }}"
                                        value="{{ $educational_program['uid'] }}">{{ $educational_program['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="educational_program_uid" />
                        </div>
                    </div>
                </div>

                <div class="field mt-2">
                    <div class="label-container label-center">
                        <label for="educational_program_type_uid">Tipo de programa formativo <span
                                class="text-danger">*</span></label>
                    </div>
                    <div class="content-container mt-1">
                        <div class="select-container">
                            <select id="educational_program_type_uid" name="educational_program_type_uid"
                                class="poa-select w-full">
                                <option value="" selected>Ninguno</option>
                                @foreach ($educationals_programs_types as $educational_program_type)
                                    <option value="{{ $educational_program_type['uid'] }}">
                                        {{ $educational_program_type['name'] }}</option>
                                @endforeach
                            </select>

                            <input type="hidden" name="educational_program_type_uid" />
                        </div>
                    </div>
                </div>

                <div class="field mt-2">
                    <div class="label-container label-center">
                        <label for="course_type_uid">Tipo de curso <span class="text-danger">*</span></label>
                    </div>

                    <div class="content-container mt-1">
                        <div class="select-container">
                            <select id="course_type_uid" name="course_type_uid" class="poa-select w-full">
                                <option value="" selected>Selecciona tipo de curso</option>
                                @foreach ($courses_types as $course_type)
                                    <option value="{{ $course_type['uid'] }}">
                                        {{ $course_type['name'] }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="course_type_uid" />

                        </div>
                    </div>
                </div>

                <div class="field mt-2">
                    <div class="label-container label-center">
                        <label for="min_required_students">Mínimo de estudiantes requeridos</label>
                    </div>

                    <div class="content-container mt-1">
                        <input type="number" class="poa-input" id="min_required_students" name="min_required_students"
                            value="0" />
                    </div>
                </div>

                <div class="field mt-2">
                    <div class="label-container label-center">
                        <label for="center">Centro <span class="text-danger">*</span></label>
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

                <section id="inscription-dates">
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

                <div class="field mt-2">
                    <div class="label-container">
                        <label for="image_input_file">Imagen</label>
                    </div>
                    <div class="content-container mt-1">

                        <div class="poa-input-image">
                            <img id="image_path_preview" src="{{ env('NO_IMAGE_SELECTED_PATH') }}" />

                            <span class="dimensions">*Dimensiones: Alto: 50px x Ancho: 300px. Formato: PNG, JPG. Tam.
                                Máx.: 1MB</span>

                            <div class="select-file-container">
                                <input accept="image/*" type="file" id="image_input_file" name="image_input_file"
                                    class="hidden" />

                                <div class="flex items-center gap-[20px]">
                                    <label for="image_input_file" class="btn btn-rectangular">
                                        Subir {{ e_heroicon('arrow-up-tray', 'outline') }}
                                    </label>

                                    <span class="image-name text-[14px]">Ningún archivo seleccionado</span>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>

                <div class="field mt-2">
                    <div class="label-container label-center">
                        <label for="calification_type">Tipo de calificación <span class="text-danger">*</span></label>
                    </div>

                    <div class="content-container mt-1">
                        <div class="select-container">
                            <select id="calification_type" name="calification_type" class="poa-select w-full">
                                <option value="" selected>Selecciona tipo de calificación</option>
                                <option value="NUMERICAL">Numérica</option>
                                <option value="TEXTUAL">Textual</option>
                            </select>
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
                    <div class="label-container">
                        <label for="objectives">Objetivos</label>
                    </div>
                    <div class="content-container mt-1">
                        <textarea placeholder="Los objetivos del curso son los siguientes..." rows="5" class="poa-input"
                            id="objectives" name="objectives"></textarea>
                    </div>
                </div>

                <div class="field mt-2">
                    <div class="label-container label-center">
                        <label for="validate_student_registrations">Validar registros de estudiantes</label>
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

                <div class="field mt-2 no-visible" id="criteria-area">
                    <div class="label-container">
                        <label for="evaluation_criteria">Criterio de validación <span
                                class="text-danger">*</span></label>
                    </div>

                    <div class="content-container mt-1">
                        <textarea placeholder="Los criterios de evaluación son los siguientes..." rows="5" class="poa-input"
                            id="evaluation_criteria" name="evaluation_criteria"></textarea>
                    </div>
                </div>

                <div class="hidden mt-2" id="validation-information-field">
                    <div class="label-container label-center">
                        <label for="validation_information">Información de validación</label>
                    </div>

                    <div class="content-container">
                        <textarea placeholder="Los estudiantes deberán cumplir los siguientes requisitos..." rows="5" class="poa-input"
                            name="validation_information" id="validation_information"></textarea>
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
                        <label for="tags">Etiquetas</label>
                    </div>

                    <div class="content-container mt-1" id="tags-container">
                        <input id="tags" name="tags" autocomplete="off" name="tags"
                            placeholder="Introduce etiquetas" />
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
                    <div class="label-container label-center">
                        <label for="lms_url">URL LMS</label>
                    </div>
                    <div class="content-container mt-1">
                        <input class="poa-input" placeholder="moodle url" type="text" id="lms_url"
                            name="lms_url" />
                    </div>
                </div>

                <div class="field mt-2">
                    <div class="label-container label-center">
                        <label for="teachers-no-coordinators">Docentes coordinadores</label>
                    </div>
                    <div class="content-container mt-1" id="teachers-container">
                        <select id="teachers-coordinators" class="mb-4" name="teacher_coordinators[]" multiple
                            placeholder="Selecciona un docente coordinador..." autocomplete="off">
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
                        <select id="teachers-no-coordinators" class="mb-4" name="teacher_no_coordinators[]" multiple
                            placeholder="Selecciona un docente no coordinador..." autocomplete="off">
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher['uid'] }}">
                                    {{ $teacher['first_name'] }} {{ $teacher['last_name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

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
                    </div>

                </div>

                <div class="field mt-2">
                    <div class="label-container label-center">
                        <label for="cost">Coste (€)</label>
                    </div>
                    <div class="content-container mt-1">
                        <input type="number" placeholder="100€" step="any" class="poa-input" id="cost"
                            name="cost" value="0" />
                    </div>

                </div>

                <div class="field">
                    <div class="label-container">
                        <label>Documentos necesarios para la inscripción</label>
                    </div>

                    <div class="content-container" id="document-container">
                        <div class="document-list" id="document-list">

                        </div>

                        <div class="flex justify-end">
                            <div>
                                <button type="button" class="btn-icon" id="btn-add-document">
                                    {{ e_heroicon('plus', 'outline') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <section id="carrousel-big">
                    <div class="field mt-2">
                        <div class="label-container label-center">
                            <label for="featured_big_carrousel">Destacar en el carrousel grande</label>
                        </div>

                        <div class="content-container mt-1">
                            <div class="checkbox">
                                <label for="featured_big_carrousel"
                                    class="inline-flex relative items-center cursor-pointer">
                                    <input type="checkbox" id="featured_big_carrousel" name="featured_big_carrousel"
                                        class="sr-only peer">
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
                                <label for="featured_big_carrousel_title">Título en el carrousel grande</label>
                            </div>
                            <div class="content-container mt-1">
                                <input type="text" placeholder="Curso en bellasartes" class="poa-input"
                                    id="featured_big_carrousel_title" name="featured_big_carrousel_title" />
                            </div>
                        </div>

                        <div class="field mt-2">
                            <div class="label-container">
                                <label for="featured_big_carrousel_description">Descripción en el carrousel
                                    grande</label>
                            </div>

                            <div class="content-container mt-1">
                                <textarea
                                    placeholder="La aportación más significativa del grado en bellas artes es la de formar artistas capaces de aportar criterios..."
                                    rows="5" class="poa-input" id="featured_big_carrousel_description"
                                    name="featured_big_carrousel_description"></textarea>
                            </div>
                        </div>

                        <div class="field mt-2">
                            <div class="label-container">
                                <label for="featured_big_carrousel_image_path">Imagen en el carrousel grande</label>
                            </div>
                            <div class="content-container mt-1">
                                <div class="poa-input-image">
                                    <img id="featured_big_carrousel_image_path_preview"
                                        src="{{ env('NO_IMAGE_SELECTED_PATH') }}" />

                                    <div class="select-file-container">
                                        <input accept="image/*" type="file" id="featured_big_carrousel_image_path"
                                            name="featured_big_carrousel_image_path" class="hidden" />

                                        <div class="flex items-center gap-[20px]">
                                            <label for="featured_big_carrousel_image_path"
                                                class="btn btn-rectangular">
                                                Subir {{ e_heroicon('arrow-up-tray', 'outline') }}
                                            </label>

                                            <span class="image-name text-[14px]">Ningún archivo seleccionado</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </section>

                <section id="carrousel-small">
                    <div class="field mt-2">
                        <div class="label-container label-center">
                            <label for="featured_small_carrousel">Destacar en el carrousel pequeño</label>
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

                <h2 class="my-[40px]">Editar bloques y elementos</h2>

                <div class=" mt-2" id="course-composition-block">

                    <div id="course-composition" class="space-y-4">

                    </div>
                    <button type="button" id="addBlock" class="btn btn-primary mt-4">Añadir Bloque
                        {{ e_heroicon('plus', 'outline') }}</button>

                    <template id="block-template">
                        <div class="composition block p-4 border rounded" data-uid="" data-order="">
                            <div class="flex flex-col">
                                <button type="button" class="removeBlock btn-remove">Eliminar bloque
                                    {{ e_heroicon('trash', 'outline') }}</button>

                                <select class="block-type border-full border-x">
                                    <option disabled value="">Tipo de bloque</option>
                                    <option value="THEORETIC">Teórico</option>
                                    <option value="PRACTICAL">Práctico</option>
                                    <option value="EVALUATION">Evaluación</option>
                                </select>
                                <input type="text" class="block-name border-full border"
                                    placeholder="Nombre del bloque">

                                <textarea class="block-description border-full " placeholder="Descripción del bloque"></textarea>

                                <div class="border-primary border-x rounded-b border-b competences-section max-h-[]"
                                    data-order=""></div>

                            </div>
                            <div class="sub-blocks ml-4"></div>
                            <button type="button" class="addSubBlock btn btn-primary mt-4">Añadir Sub-Bloque
                                {{ e_heroicon('plus', 'outline') }}</button>
                        </div>
                    </template>

                    <template id="sub-block-template">
                        <div class="composition sub-block p-4 border rounded mt-2" data-subblock-uid=""
                            data-order="">
                            <div class="flex flex-col">

                                <button type="button" class="removeSubBlock btn-composition btn-remove">Eliminar
                                    Sub-bloque
                                    {{ e_heroicon('trash', 'outline') }}</button>

                                <input type="text" class="input-field p-2 rounded border sub-block-name"
                                    placeholder="Nombre del Sub-Bloque">
                                <textarea class="textarea-field p-2 rounded border sub-block-description" placeholder="Descripción del sub-bloque"></textarea>
                            </div>
                            <div class="elements ml-4"></div>
                            <button type="button" class="addElement btn btn-primary mt-4">Añadir elemento
                                {{ e_heroicon('plus', 'outline') }}</button>
                        </div>
                    </template>

                    <template id="element-template">
                        <div class="composition element p-4 border rounded mt-2" data-uid="" data-order="">
                            <div class="flex flex-col">

                                <button type="button" class="removeElement btn-remove">Eliminar Elemento
                                    {{ e_heroicon('trash', 'outline') }}</button>

                                <input type="text" class="input-field p-2 rounded border element-name"
                                    placeholder="Nombre del elemento">
                                <textarea class="textarea-field p-2 rounded border element-description" placeholder="Descripción del elemento"></textarea>
                            </div>
                            <div class="sub-elements ml-4"></div>

                            <button type="button" class="addSubElement btn btn-primary mt-4">Añadir Sub-Elemento
                                {{ e_heroicon('plus', 'outline') }}</button>
                        </div>
                    </template>

                    <template id="sub-element-template">
                        <div class="composition sub-element p-4 border rounded mt-2" data-uid="" data-order="">
                            <div class="flex flex-col">
                                <button type="button" class="removeSubElement btn-remove">Eliminar Sub-Elemento
                                    {{ e_heroicon('trash', 'outline') }}</button>

                                <input type="text" class="input-field p-2 rounded border sub-element-name"
                                    placeholder="Nombre Sub-Elemento">
                                <textarea class="textarea-field p-2 rounded border sub-element-description"
                                    placeholder="Descripción del Sub-Elemento"></textarea>
                            </div>
                        </div>
                    </template>

                </div>

                <input type="hidden" id="course_uid" name="course_uid" value="" />

                <div class="flex justify-center mt-8 gap-4" id="btns-save">

                    <div id="draft-button-container" class="hidden">
                        <button type="submit" value="draft" id="draft-button" class="btn btn-secondary">
                            Guardar como borrador {{ e_heroicon('check', 'outline') }}</button>
                    </div>

                    <button type="submit" value="submit" id="submit-button" class="btn btn-primary">
                        Guardar {{ e_heroicon('paper-airplane', 'outline') }}</button>
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
                    {{ e_heroicon('trash', 'outline') }}
                </button>
            </div>
        </div>
    </div>
</template>
