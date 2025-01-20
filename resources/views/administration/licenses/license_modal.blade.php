<div id="license-modal" class="modal">

    <div class="modal-body w-full md:max-w-[950px]">

        <div class="modal-header">
            <div>
                <h2 id="license-modal-title" class="modal-title">Licencia</h2>
            </div>

            <div>
                <button data-modal-id="license-modal" class="modal-close-modal-btn close-modal-btn">

                    <?php eHeroicon('x-mark', 'solid'); ?>
                </button>
            </div>

        </div>

        <form id="license-form">
            @csrf

            <div class="poa-form">
                <div class="field">
                    <div class="label-container label-center">
                        <label for="name">Nombre <span class="text-red-500">*</span></label>
                    </div>
                    <div class="content-container">
                        <input maxlength="255" class="required poa-input" placeholder="Licencia" type="text" id="name"
                            name="name" />
                    </div>
                </div>

                <div class="btn-block">
                    <button type="submit" class="btn btn-primary">
                        Guardar {{ eHeroicon('paper-airplane', 'outline') }}</button>

                    <button data-modal-id="license-modal" type="button"
                        class="btn btn-secondary close-modal-btn">Cancelar {{ eHeroicon('x-mark', 'outline') }}</button>
                </div>
            </div>

            <input type="hidden" id="license_uid" name="license_uid" value="" />

        </form>

    </div>

</div>
