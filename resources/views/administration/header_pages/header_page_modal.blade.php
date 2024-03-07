<div id="header-page-modal" class="modal">

    <div class="modal-body w-full md:max-w-[950px]">

        <div class="modal-header">
            <div>
                <h2 class="modal-title"></h2>
            </div>

            <div>
                <button data-modal-id="header-page-modal" class="modal-close-modal-btn close-modal-btn">

                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>
            </div>

        </div>


        <form id="header-page-form">
            @csrf

            <div class="poa-form">
                <div class="field">
                    <div class="label-container label-center">
                        <label for="name">Nombre <span class="text-red-500">*</span></label>
                    </div>
                    <div class="content-container">
                        <input class="required poa-input" placeholder="Aviso legal" type="text" id="name"
                            name="name" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="name">Contenido <span class="text-red-500">*</span></label>
                    </div>
                    <div class="content-container">
                        <textarea id="header-page-content">Hello, World!</textarea>
                    </div>
                </div>


                <div class="flex justify-center mt-8 gap-4">

                    <button type="submit" value="submit" id="submit-button" class="btn btn-primary">
                        Guardar {{ e_heroicon('paper-airplane', 'outline') }}</button>

                    <button data-modal-id="header-page-modal" type="button"
                        class="btn btn-secondary close-modal-btn">Cancelar
                        {{ e_heroicon('x-mark', 'outline') }}</button>
                </div>

            </div>

            <input type="hidden" id="header_page_uid" name="header_page_uid" />

        </form>

    </div>

</div>
