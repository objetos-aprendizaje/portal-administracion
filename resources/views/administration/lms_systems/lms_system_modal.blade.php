<div id="lms-system-modal" class="modal">

    <div class="modal-body w-full md:max-w-[950px]">

        <div class="modal-header">
            <div>
                <h2 id="lms-system-modal-title" class="modal-title">Sistema LMS</h2>
            </div>

            <div>
                <button data-modal-id="lms-system-modal" class="modal-close-modal-btn close-modal-btn">

                    <?php eHeroicon('x-mark', 'solid'); ?>
                </button>
            </div>

        </div>

        <form id="lms-system-form">
            @csrf

            <div class="poa-form">
                <div class="field">
                    <div class="label-container label-center">
                        <label for="name">Nombre <span class="text-red-500">*</span></label>
                    </div>
                    <div class="content-container">
                        <input maxlength="255" class="required poa-input" placeholder="Moodle" type="text" id="name"
                            name="name" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="name">Identificador <span class="text-red-500">*</span></label>
                    </div>
                    <div class="content-container">
                        <input maxlength="255" class="required poa-input" placeholder="moodle" type="text" id="identifier"
                            name="identifier" />
                    </div>
                </div>

                <div class="btn-block">
                    <button type="submit" class="btn btn-primary">
                        Guardar {{ eHeroicon('paper-airplane', 'outline') }}</button>

                    <button data-modal-id="lms-system-modal" type="button"
                        class="btn btn-secondary close-modal-btn">Cancelar {{ eHeroicon('x-mark', 'outline') }}</button>
                </div>
            </div>

            <input type="hidden" id="lms_system_uid" name="lms_system_uid" value="" />

        </form>

    </div>

</div>
