<div id="tooltip-texts-modal" class="modal">

    <div class="modal-body w-full md:max-w-[950px]">

        <div class="modal-header">
            <div>
                <h2 id="tooltip-texts-modal-title" class="modal-title"></h2>
            </div>

            <div>
                <button data-modal-id="tooltip-texts-modal" class="modal-close-modal-btn close-modal-btn">

                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>
            </div>

        </div>

        <form id="tooltip-texts-form">
            @csrf

            <div class="poa-form">
                <div class="field">
                    <div class="label-container label-center">
                        <label for="input_id">ID del campo <span class="text-red-500">*</span></label>
                    </div>
                    <div class="content-container">
                        <input maxlength="100" class="required poa-input" placeholder="company_name" type="text" id="input_id"
                            name="input_id" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container">
                        <label for="description">Descripción <span class="text-red-500">*</span></label>
                    </div>
                    <div class="content-container">
                        <textarea maxlength="1000" class="required poa-input" rows="4" placeholder="Campo destinado al nombre de la empresa." type="text" id="description"
                            name="description"></textarea>
                    </div>
                </div>

                <div class="btn-block">
                    <button type="submit" class="btn btn-primary">
                        Guardar {{ e_heroicon('paper-airplane', 'outline') }}</button>

                    <button data-modal-id="center-modal" type="button"
                        class="btn btn-secondary close-modal-btn">Cancelar {{ e_heroicon('x-mark', 'outline') }}</button>
                </div>
            </div>

            <input type="hidden" id="tooltip_text_uid" name="tooltip_text_uid" value="" />

        </form>

    </div>

</div>
