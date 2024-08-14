<div id="department-modal" class="modal">

    <div class="modal-body w-full md:max-w-[950px]">

        <div class="modal-header">
            <div>
                <h2 id="department-modal-title" class="modal-title"></h2>
            </div>

            <div>
                <button data-modal-id="department-modal" class="modal-close-modal-btn close-modal-btn">

                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>
            </div>

        </div>

        <form id="department-form">
            @csrf

            <div class="poa-form">
                <div class="field">
                    <div class="label-container label-center">
                        <label for="name">Nombre <span class="text-red-500">*</span></label>
                    </div>
                    <div class="content-container">
                        <input maxlength="255" class="required poa-input" placeholder="Departamento" type="text" id="name"
                            name="name" />
                    </div>
                </div>

                <div class="btn-block">
                    <button type="submit" class="btn btn-primary">
                        Guardar {{ e_heroicon('paper-airplane', 'outline') }}</button>

                    <button data-modal-id="center-modal" type="button"
                        class="btn btn-secondary close-modal-btn">Cancelar {{ e_heroicon('x-mark', 'outline') }}</button>
                </div>
            </div>

            <input type="hidden" id="department_uid" name="department_uid" value="" />

        </form>

    </div>

</div>
