<div id="educational-program-type-modal" class="modal">

    <div class="modal-body w-full md:max-w-[700px]">

        <div class="modal-header">
            <div>
                <h2 class="modal-title">Añade un nuevo tipo de programa formativo</h2>
            </div>

            <div>
                <button data-modal-id="educational-program-type-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>


        <form id="educational-program-type-form">
            @csrf

            <div class="poa-form">

                <div class="field">
                    <div class="label-container label-center">
                        <label for="name">Nombre <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container">
                        <input maxlength="255" type="text" id="name" name="name" class="poa-input" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container">
                        <label for="description">Descripción</label>
                    </div>
                    <div class="content-container">
                        <textarea maxlength="1000" id="description" name="description" class="poa-input"></textarea>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="managers_can_emit_credentials">Los gestores pueden emitir credenciales</label>
                    </div>

                    <div class="content-container flex items-center">
                        <div class="checkbox">
                            <label for="managers_can_emit_credentials" class="inline-flex relative items-center cursor-pointer">
                                <input type="checkbox"
                                    id="managers_can_emit_credentials" name="managers_can_emit_credentials" class="sr-only peer">
                                <div
                                    class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="teachers_can_emit_credentials">Los profesores pueden emitir credenciales</label>
                    </div>

                    <div class="content-container flex items-center">
                        <div class="checkbox">
                            <label for="teachers_can_emit_credentials" class="inline-flex relative items-center cursor-pointer">
                                <input type="checkbox"
                                    id="teachers_can_emit_credentials" name="teachers_can_emit_credentials" class="sr-only peer">
                                <div
                                    class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <input type="hidden" id="educational_program_type_uid" name="educational_program_type_uid"
                    value="" />

                <div class="btn-block">
                    <button type="submit" class="btn btn-primary">Guardar {{ e_heroicon('paper-airplane', 'outline') }}</button>

                    <button data-modal-id="educational-program-type-modal" type="button"
                        class="btn btn-secondary close-modal-btn">Cancelar {{ e_heroicon('x-mark', 'outline') }}</button>
                </div>

            </div>

        </form>

    </div>

</div>
