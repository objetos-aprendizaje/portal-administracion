<div id="api-key-modal" class="modal">

    <div class="modal-body w-full md:max-w-[700px]">

        <div class="modal-header">
            <div>
                <h2 id="api-key-modal-title" class="modal-title"></h2>

            </div>
            <div>
                <button data-modal-id="api-key-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>

        <form id="api-key-form">
            @csrf

            <div class="poa-form">


                <div class="field">
                    <div class="label-container label-center">
                        <label for="name">Nombre <span class="text-red-500">*</span></label>
                    </div>

                    <div class="content-container">
                        <input maxlength="255" type="text" id="name" name="name" class="poa-input"
                            placeholder="Nombre" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="api_key">Clave API <span class="text-red-500">*</span></label>
                    </div>

                    <div class="content-container">
                        <input maxlength="50" type="text" id="api_key" name="api_key" class="poa-input"
                            placeholder="api_key" />
                    </div>
                </div>

                <div class="flex justify-center mt-8">
                    <button id="add-api-key-btn" type="submit" class="btn btn-primary">Guardar {{e_heroicon('paper-airplane', 'outline')}}</button>
                </div>

            </div>

            <input type="hidden" id="api_key_uid" name="api_key_uid" value="" />

        </form>

    </div>

</div>
