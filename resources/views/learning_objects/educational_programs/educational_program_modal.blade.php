<div id="educational-program-modal" class="modal">

    <div class="modal-body w-full md:max-w-[1200px]">

        <div class="modal-header">
            <div>
                <h2 class="modal-title"></h2>
            </div>

            <div>
                <button data-modal-id="educational-program-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>

        <form id="educational-program-form" enctype="multipart/form-data">
            @csrf

            <div class="poa-form">
                <div class="field">
                    <div class="label-container label-center">
                        <label for="name">Nombre <span class="text-red-500">*</span></label>
                    </div>
                    <div class="content-container">
                        <input maxlength="255" class="required poa-input" placeholder="Programa formativo de..."
                            type="text" id="name" name="name" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container">
                        <label for="description">Descripción</label>
                    </div>
                    <div class="content-container">
                        <textarea maxlength="1000" class="w-full poa-input" placeholder="La convocatoria de este año tratará de..." rows="5"
                            id="description" name="description"></textarea>
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

                <div class="field">
                    <div class="label-container label-center">
                        <label for="educational_program_type_uid">Tipo de programa formativo <span
                                class="text-red-500">*</span></label>
                    </div>

                    <div class="content-container">
                        <select class="poa-select w-full" id="educational_program_type_uid"
                            name="educational_program_type_uid">
                            <option value="">Selecciona un tipo de programa formativo</option>
                            @foreach ($educational_program_types as $program_type)
                                <option value="{{ $program_type['uid'] }}">{{ $program_type['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>

                @if ($general_options['operation_by_calls'])
                    <div class="field">
                        <div class="label-container label-center">
                            <label for="call_uid">Convocatoria <span class="text-danger">*</span></label>
                        </div>

                        <div class="content-container">
                            <select class="poa-select w-full" id="call_uid" name="call_uid">
                                <option value="">Selecciona una convocatoria</option>
                                @foreach ($calls as $call)
                                    <option value="{{ $call['uid'] }}">{{ $call['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif

                <div class="field">
                    <div class="label-container label-center">
                        <label for="inscription_start_date">Fecha de inicio de inscripción <span
                                class="text-danger">*</span></label>
                    </div>
                    <div class="content-container mt-1">
                        <input type="datetime-local" class="poa-input" id="inscription_start_date"
                            name="inscription_start_date" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="inscription_finish_date">Fecha de fin de inscripción <span
                                class="text-danger">*</span></label>
                    </div>
                    <div class="content-container mt-1">
                        <input type="datetime-local" class="poa-input" id="inscription_finish_date"
                            name="inscription_finish_date" />
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

                <section id="criteria-area">
                    <div class="field mt-2">
                        <div class="label-container">
                            <label for="evaluation_criteria">Criterio de validación <span
                                    class="text-danger">*</span></label>
                        </div>

                        <div class="content-container mt-1">
                            <textarea maxlength="1000" placeholder="Los criterios de evaluación son los siguientes..." rows="5" class="poa-input"
                                id="evaluation_criteria" name="evaluation_criteria"></textarea>
                        </div>
                    </div>
                </section>

                <section id="documents-container">
                    <div class="field mt-2">
                        <div class="label-container">
                            <label>Documentos necesarios para el programa formativo</label>
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
                </section>

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
                    <div class="label-container label-center" id="label-container-cost">
                        <label for="cost">Coste (€)</label>
                    </div>
                    <div class="content-container mt-1">
                        <input type="number" placeholder="100€" step="any" class="poa-input" id="cost"
                            name="cost" value="0" />
                        <div id="payment_terms" class="hidden">
                            <div id="payment-terms-list">

                            </div>

                            <div class="flex justify-end">
                                <div>
                                    <button type="button" class="btn-icon" id="btn-add-payment">
                                        {{ e_heroicon('plus', 'outline') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <section class="hidden" id="enrolling-dates-container">
                    <div class="field">
                        <div class="label-container label-center">
                            <label for="enrolling_start_date">Fecha de inicio de matriculación <span
                                    class="text-danger">*</span></label>
                        </div>
                        <div class="content-container mt-1">
                            <input type="datetime-local" class="poa-input" id="enrolling_start_date"
                                name="enrolling_start_date" />
                        </div>
                    </div>

                    <div class="field">
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


                <div class="field">
                    <div class="label-container label-center">
                        <label for="realization_start_date">Fecha de inicio de realización <span
                                class="text-danger">*</span></label>
                    </div>
                    <div class="content-container mt-1">
                        <input type="datetime-local" class="poa-input" id="realization_start_date"
                            name="realization_start_date" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="realization_finish_date">Fecha de fin de realización <span
                                class="text-danger">*</span></label>
                    </div>
                    <div class="content-container mt-1">
                        <input type="datetime-local" class="poa-input" id="realization_finish_date"
                            name="realization_finish_date" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container">
                        <label for="courses">Cursos</label>
                    </div>

                    <div class="content-container">
                        <select id="courses" class="mb-4" name="courses[]" multiple
                            placeholder="Selecciona uno o varios cursos" autocomplete="off">
                        </select>
                    </div>

                </div>

                <div class="field">
                    <div class="label-container">
                        <label for="image_path">Imagen</label>
                    </div>
                    <div class="content-container mt-1">
                        <div class="poa-input-image">
                            <img id="image_path_preview" src="{{ env('NO_IMAGE_SELECTED_PATH') }}" />

                            <div class="select-file-container">
                                <input accept="image/*" type="file" id="image_path" name="image_path"
                                    class="hidden" />

                                <div class="flex items-center gap-[20px]">
                                    <label for="image_path" class="btn btn-rectangular">
                                        Subir {{ e_heroicon('arrow-up-tray', 'outline') }}
                                    </label>

                                    <span class="image-name text-[14px]">Ningún archivo seleccionado</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <section id="feature-main-slider-container">
                    <div class="field mt-2">
                        <div class="label-container label-center">
                            <label for="featured_slider">Destacar en el slider principal</label>
                        </div>

                        <div class="content-container mt-1">
                            <div class="checkbox">
                                <label for="featured_slider" class="inline-flex relative items-center cursor-pointer">
                                    <input type="checkbox" id="featured_slider" name="featured_slider"
                                        class="sr-only peer">
                                    <div
                                        class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="hidden" id="featured-slider-info">
                        <div class="field mt-2">
                            <div class="label-container label-center">
                                <label for="featured_slider_title">Título en el slider</label>
                            </div>
                            <div class="content-container mt-1">
                                <input maxlength="255" type="text" placeholder="Curso en bellasartes"
                                    class="poa-input" id="featured_slider_title" name="featured_slider_title" />
                            </div>
                        </div>

                        <div class="field mt-2">
                            <div class="label-container">
                                <label for="featured_slider_description">Descripción en el slider</label>
                            </div>

                            <div class="content-container mt-1">
                                <textarea maxlength="1000"
                                    placeholder="La aportación más significativa del grado en bellas artes es la de formar artistas capaces de aportar criterios..."
                                    rows="5" class="poa-input" id="featured_slider_description" name="featured_slider_description"></textarea>
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
                                <label for="featured_slider_image_path">Imagen en el slider</label>
                            </div>
                            <div class="content-container mt-1">
                                <div class="poa-input-image mb-2">
                                    <img id="featured_slider_image_path_preview"
                                        src="{{ env('NO_IMAGE_SELECTED_PATH') }}" />

                                    <div class="select-file-container">
                                        <input accept="image/*" type="file" id="featured_slider_image_path"
                                            name="featured_slider_image_path" class="hidden" />

                                        <div class="flex items-center gap-[20px]">
                                            <label for="featured_slider_image_path" class="btn btn-rectangular">
                                                Subir {{ e_heroicon('arrow-up-tray', 'outline') }}
                                            </label>

                                            <span class="image-name text-[14px]">Ningún archivo seleccionado</span>
                                        </div>
                                    </div>
                                </div>
                                <a id="previsualize-slider" href="javascript:void(0)">Previsualizar slider</a>
                            </div>
                        </div>

                    </div>

                </section>

                <section id="feature-main-carrousel-container">
                    <div class="field mt-2">
                        <div class="label-container label-center">
                            <label for="featured_main_carrousel">Destacar en el carrousel</label>
                        </div>

                        <div class="content-container mt-1">
                            <div class="checkbox">
                                <label for="featured_main_carrousel"
                                    class="inline-flex relative items-center cursor-pointer">
                                    <input type="checkbox" id="featured_main_carrousel"
                                        name="featured_main_carrousel" class="sr-only peer">
                                    <div
                                        class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="field mt-2">
                    <div class="label-container label-center">
                        <label for="contact_emails">Emails de contacto</label>
                    </div>
                    <div class="content-container mt-1">
                        <input id="contact_emails" autocomplete="off" name="contact_emails"
                            placeholder="Introduce emails de contacto" />
                    </div>
                </div>

                <div class="btn-block" id="btns-save">
                    <div id="draft-button-container" class="hidden">
                        <button type="submit" value="draft" id="draft-button" class="btn btn-secondary">
                            Guardar como borrador {{ e_heroicon('check', 'outline') }}</button>
                    </div>

                    <button type="submit" value="submit" id="submit-button" class="btn btn-primary ">
                        Guardar {{ e_heroicon('paper-airplane', 'outline') }}</button>
                </div>

            </div>

            <input type="hidden" id="educational_program_uid" name="educational_program_uid" value="" />

        </form>

    </div>

</div>

<template id="document-template">
    <div class="document" data-document-uid="">
        <div class="flex gap-2 mb-2">
            <input maxlength="255" type="text" class="poa-input document-name" placeholder="Nombre" />
            <div class="flex-none">
                <button type="button" class="btn-icon btn-remove-document">
                    {{ e_heroicon('trash', 'outline') }}
                </button>
            </div>
        </div>
    </div>
</template>

<template id="payment-term-template">
    <div class="payment-term">
        <div class="mb-2">
            <input type="text" class="poa-input payment-term-name" placeholder="Información del plazo" />
        </div>

        <div class="flex gap-2 my-2">

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
                {{ e_heroicon('trash', 'outline') }}
            </button>
        </div>
    </div>
</template>
