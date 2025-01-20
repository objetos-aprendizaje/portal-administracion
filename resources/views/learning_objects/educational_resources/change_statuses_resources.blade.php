<div id="change-statuses-resources-modal" data-action="" class="modal">
    <div class="modal-body w-full md:max-w-[900px]">

        <div class="modal-header">
            <div>
                <h2 class="modal-title">Cambio de estado de recursos</h2>
            </div>

            <div>
                <button data-modal-id="change-statuses-resources-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php eHeroicon('x-mark', 'solid'); ?>
                </button>
            </div>

        </div>

        <p class="mb-2">Vas a cambiar el estado a los siguientes recursos:</p>

        <div id="resources-list"></div>

        <div class="btn-block">
            <button id="confirm-change-statuses-btn" class="btn btn-primary">Guardar {{ eHeroicon('paper-airplane', 'outline') }}</button>
            <button data-modal-id="change-statuses-resources-modal" class="btn btn-secondary close-modal-btn">Cancelar {{ eHeroicon('x-mark', 'outline') }}</button>
        </div>

    </div>
    <div class="params"></div>

</div>
