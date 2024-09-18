<div id="educational-resource-types-modal" class="modal">

    <div class="modal-body w-full md:max-w-[700px]">

        <div class="modal-header">
            <div>
                <h2 class="modal-title">Añade un nuevo tipo de recurso educativo</h2>
            </div>

            <div>
                <button data-modal-id="educational-resource-types-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>

        <form id="educational-resource-type-form">
            @csrf

            <div class="poa-form">

                <div class="field">
                    <div class="label-container">
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

                <input type="hidden" id="educational_resource_type_uid" name="educational_resource_type_uid"
                    value="" />

                <div class="btn-block">
                    <button type="submit" class="btn btn-primary">
                        Guardar {{ e_heroicon('paper-airplane', 'outline') }}</button>

                    <button data-modal-id="educational-resource-types-modal" type="button"
                        class="btn btn-secondary close-modal-btn btn-close-modal-educational-resource-type">Cancelar
                        {{ e_heroicon('x-mark', 'outline') }}</button>
                </div>

            </div>

        </form>

    </div>

</div>
