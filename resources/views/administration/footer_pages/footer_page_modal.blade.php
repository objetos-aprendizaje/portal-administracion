<div id="footer-page-modal" class="modal">

    <div class="modal-body w-full md:max-w-[950px]">

        <div class="modal-header">
            <div>
                <h2 class="modal-title">Información de footer</h2>
            </div>

            <div>
                <button data-modal-id="footer-page-modal" class="modal-close-modal-btn close-modal-btn">

                    <?php eHeroicon('x-mark', 'solid'); ?>
                </button>
            </div>

        </div>


        <form id="footer-page-form">
            @csrf

            <div class="poa-form">
                <div class="field">
                    <div class="label-container label-center">
                        <label maxlength="255" for="name">Nombre <span class="text-red-500">*</span></label>
                    </div>
                    <div class="content-container">
                        <input class="required poa-input" placeholder="Aviso legal" type="text" id="name"
                            name="name" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="slug">Slug <span class="text-red-500">*</span></label>
                    </div>
                    <div class="content-container">
                        <input maxlength="255" class="required poa-input" placeholder="aviso-legal" type="text"
                            id="slug" name="slug" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container">
                        <label for="footer-page-content">Contenido <span class="text-red-500">*</span></label>
                    </div>
                    <div class="content-container">
                        <textarea maxlength="1000" id="footer-page-content">Hello, World!</textarea>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="version">Versión <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container">
                        <input placeholder="1" type="number" id="version" name="version" class="poa-input" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container">
                        <label for="acceptance_required">Aceptación requerida</label>
                    </div>
                    <div class="content-container">
                        <div class="checkbox mb-2">
                            <label for="acceptance_required" class="inline-flex relative items-center cursor-pointer">
                                <input type="checkbox" id="acceptance_required" name="acceptance_required"
                                    class="sr-only peer">
                                <div
                                    class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                                </div>
                                <div class="checkbox-name"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex justify-center mt-8 gap-4">

                    <button type="submit" value="submit" id="submit-button" class="btn btn-primary">
                        Guardar {{ eHeroicon('paper-airplane', 'outline') }}</button>

                    <button data-modal-id="footer-page-modal" type="button"
                        class="btn btn-secondary close-modal-btn">Cancelar
                        {{ eHeroicon('x-mark', 'outline') }}</button>
                </div>

            </div>

            <input type="hidden" id="footer_page_uid" name="footer_page_uid" />

        </form>

    </div>

</div>
