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
                    <div class="label-container label-center">
                        <label for="educational_program_type_uid">Tipo de programa formativo <span class="text-red-500">*</span></label>
                    </div>

                    <div class="content-container">
                        <select class="poa-select w-full" id="educational_program_type_uid" name="educational_program_type_uid">
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
                    <div class="label-container">
                        <label for="select-courses">Cursos</label>
                    </div>

                    <div class="content-container">
                        <select id="select-courses" class="mb-4" name="courses[]" multiple placeholder="Selecciona uno o varios cursos"
                             autocomplete="off">
                        </select>
                    </div>

                </div>

                <div class="btn-block">
                    <button type="submit" class="btn btn-primary">
                        Guardar {{ e_heroicon('paper-airplane', 'outline') }}</button>

                        <button data-modal-id="educational-program-modal" type="button" class="btn btn-secondary close-modal-btn">
                            Cancelar {{ e_heroicon('x-mark', 'outline') }}</button>
                </div>
            </div>

            <input type="hidden" id="educational_program_uid" name="educational_program_uid" value=""/>

        </form>

    </div>

</div>
