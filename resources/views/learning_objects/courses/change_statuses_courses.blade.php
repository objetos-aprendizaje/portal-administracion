<div id="change-statuses-courses-modal" data-action="" class="modal">
    <div class="modal-body w-full md:max-w-[900px]">

        <div class="modal-header">
            <div>
                <h2 class="modal-title">Cambio de estado de cursos</h2>
            </div>

            <div>
                <button data-modal-id="change-statuses-courses-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>
            </div>

        </div>

        <p class="mb-2">Vas a cambiar el estado a los siguientes cursos:</p>

        <div id="courses-list"></div>

        <div class="btn-block">
            <button id="confirm-change-statuses-btn" class="btn btn-primary">Guardar {{ e_heroicon('paper-airplane', 'outline') }}</button>
            <button data-modal-id="change-statuses-courses-modal" class="btn btn-secondary close-modal-btn">Cancelar {{ e_heroicon('x-mark', 'outline') }}</button>
        </div>

    </div>
    <div class="params"></div>

</div>
