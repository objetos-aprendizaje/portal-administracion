<div id="redirection-query-modal" class="modal">

    <div class="modal-body w-full md:max-w-[700px]">

        <div class="modal-header">
            <div>
                <h2 class="modal-title">Redirección de consulta</h2>

            </div>
            <div>
                <button data-modal-id="redirection-query-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php eHeroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>

        <form id="redirection-query-form">
            @csrf

            <div class="poa-form">

                <div class="field">
                    <div class="label-container label-center">
                        <label for="learning_object_type">Tipo de objeto <span
                                class="text-red-500">*</span></label>
                    </div>

                    <div class="content-container">
                        <select id="learning_object_type" name="learning_object_type"
                            class="poa-select w-full">
                            <option value="" selected>Selecciona un tipo de programa formativo</option>
                            <option value="COURSE">Cursos</option>
                            <option value="EDUCATIONAL_PROGRAM">Programas formativos</option>
                        </select>
                    </div>
                </div>

                <section id="educational-program-type-container" class="hidden">
                    <div class="field">
                        <div class="label-container label-center">
                            <label for="educational_program_type_uid">Tipo de programa formativo <span
                                    class="text-red-500">*</span></label>
                        </div>

                        <div class="content-container">
                            <select id="educational_program_type_uid" name="educational_program_type_uid"
                                class="poa-select w-full">
                                <option value="" selected>Selecciona un tipo de programa formativo</option>
                                @foreach ($educational_program_types as $program_type)
                                    <option value="{{ $program_type['uid'] }}">{{ $program_type['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </section>

                <section id="course-type-container" class="hidden">
                    <div class="field">
                        <div class="label-container label-center">
                            <label for="course_type_uid">Tipo de curso <span
                                    class="text-red-500">*</span></label>
                        </div>

                        <div class="content-container">
                            <select id="course_type_uid" name="course_type_uid"
                                class="poa-select w-full">
                                <option value="" selected>Selecciona un tipo de curso</option>
                                @foreach ($courseTypes as $courseType)
                                    <option value="{{ $courseType['uid'] }}">{{ $courseType['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </section>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="type">Tipo de redirección <span class="text-red-500">*</span></label>
                    </div>

                    <div class="content-container">
                        <select id="type" name="type" class="poa-select w-full">
                            <option value="" selected>Selecciona un tipo de redirección</option>
                            <option value="web">Web</option>
                            <option value="email">Email</option>
                        </select>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="contact">Contacto <span class="text-red-500">*</span></label>
                    </div>

                    <div class="content-container">
                        <input type="text" id="contact" name="contact" class="poa-input"
                            placeholder="Introduce el contacto" />
                    </div>
                </div>

                <div class="flex justify-center mt-8">
                    <button id="add-redirection-query-btn" type="submit" class="btn btn-primary">Guardar
                        {{ eHeroicon('paper-airplane', 'outline') }}</button>
                </div>

            </div>

            <input type="hidden" id="redirection_query_uid" name="redirection_query_uid" value="" />

        </form>

    </div>

</div>
