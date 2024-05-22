<div id="educational-program-modal" class="modal">

    <div class="modal-body w-full md:max-w-[700px]">

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
                        <input class="required poa-input" placeholder="Programa formativo de..." type="text"
                            id="name" name="name" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container">
                        <label for="description">Descripción</label>
                    </div>
                    <div class="content-container">
                        <textarea class="w-full poa-input" placeholder="La convocatoria de este año tratará de..." rows="5"
                            id="description" name="description"></textarea>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container">
                        <label for="description">Tipo <span class="text-red-500">*</span></label>
                    </div>

                    <div class="content-container">
                        <select id="is_modular" name="is_modular" class="poa-select w-full">
                            <option value="" selected>Selecciona el tipo</option>
                            <option value="1">Modular</option>
                            <option value="0">No modular</option>
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

                <div class="field">
                    <div class="label-container label-center">
                        <label for="call_uid">Convocatoria</label>
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

                <div class="field">
                    <div class="label-container">
                        <label for="select-courses">Cursos</label>
                    </div>

                    <div class="content-container">
                        <select id="select-courses" class="mb-4" name="courses[]" multiple
                            placeholder="Selecciona uno o varios cursos" autocomplete="off">
                        </select>
                    </div>

                </div>

                <div class="field">
                    <div class="label-container">
                        <label for="image_path_preview">Imagen</label>
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

                <div class="btn-block">
                    <button type="submit" class="btn btn-primary">
                        Guardar {{ e_heroicon('paper-airplane', 'outline') }}</button>

                    <button data-modal-id="educational-program-modal" type="button"
                        class="btn btn-secondary close-modal-btn">
                        Cancelar {{ e_heroicon('x-mark', 'outline') }}</button>
                </div>
            </div>

            <input type="hidden" id="educational_program_uid" name="educational_program_uid" value="" />

        </form>

    </div>

</div>
