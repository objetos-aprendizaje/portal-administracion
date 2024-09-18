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

        <div class="field mt-2">
            <div class="label-container label-center">
                <label for="bulk_change_status">Cambio masivo:</label>
            </div>
            <div class="content-container mt-1">
                <div class="select-container">
                    <select id="bulk_change_status" class="bulk_change_status poa-select mb-2 min-w-[250px]">
                        <option value="">Selecciona un estado</option>
                        <option value="REJECTED">Rechazado</option>
                        <option value="ACCEPTED">Aceptado</option>
                        <option value="UNDER_CORRECTION_APPROVAL">En subsanación para aprobación</option>
                        <option value="PENDING_PUBLICATION">Pendiente de publicación</option>
                        <option value="UNDER_CORRECTION_PUBLICATION">En subsanación para publicación</option>
                        <option value="ACCEPTED_PUBLICATION">Aceptado para publicación</option>
                        <option value="RETIRED">Retirado</option>
                    </select>
                </div>
            </div>
        </div>




        <p class="mb-2">Vas a cambiar el estado a los siguientes cursos:</p>

        <div id="courses-list"></div>

        <div class="btn-block">
            <button id="confirm-change-statuses-btn" class="btn btn-primary">Guardar
                {{ e_heroicon('paper-airplane', 'outline') }}</button>
            <button data-modal-id="change-statuses-courses-modal" class="btn btn-secondary close-modal-btn">Cancelar
                {{ e_heroicon('x-mark', 'outline') }}</button>
        </div>

    </div>
    <div class="params"></div>

</div>

<!-- Plantilla para cambiar el estado de un curso -->
<template id="change-status-course-template">
    <div class="mb-5 bg-gray-100 p-4 rounded-xl change-status-course">
        <h4 class="course-name"></h4>

        <div class="course px-4" data-uid="">
            <select class="status-course poa-select mb-2 min-w-[250px]">
                <option value="">Selecciona un estado</option>
            </select>
            <div>
                <h4>Indica un motivo</h4>
                <textarea maxlength="1000" placeholder="El estado del curso se debe a..." class="reason-status-course poa-input h-5"></textarea>
            </div>
        </div>
    </div>
</template>
