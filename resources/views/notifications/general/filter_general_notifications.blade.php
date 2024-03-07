<div id="filter-general-notifications-modal" class="modal">
    <div class="modal-body w-full md:max-w-[800px]">
        <div class="modal-header">
            <div>
                <h2 id="filter-general-notifications-modal-title">Filtrar notificaciones generales</h2>
            </div>

            <div>
                <button data-modal-id="filter-general-notifications-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>

        <div class="poa-form">

            <div class="flex gap-4 mb-4">

                <div class="w-1/2">
                    <div class="label-container label-center">
                        <label for="date_notifications">Fecha de inicio</label>
                    </div>
                    <div class="content-container mt-1">
                        <input type="datetime-local" class="poa-input" id="start_date_filter"
                            name="start_date_filter" />
                    </div>
                </div>

                <div class="w-1/2">
                    <div class="label-container label-center">
                        <label for="date_notifications">Fecha de fin</label>
                    </div>
                    <div class="content-container mt-1">
                        <input type="datetime-local" class="poa-input" id="end_date_filter"
                            name="end_date_filter" />
                    </div>
                </div>
            </div>

            <div class="flex gap-4 mb-4">
                <div class="w-1/2">
                    <div class="label-container label-center">
                        <label for="roles_filter">Tipos de notificación</label>
                    </div>

                    <div class="content-container mt-1">
                        <select id="notification_types" class="mb-4" name="notification_types[]" multiple
                            placeholder="Selecciona uno o varios tipos de notificaciones">
                        </select>
                    </div>
                </div>

            </div>
        </div>

        <div class="flex justify-center mt-8">
            <button id="filter-btn" type="button" class="btn btn-primary">
                Filtrar {{ e_heroicon('adjustments-horizontal', 'outline') }}</button>
        </div>
    </div>
</div>

</div>
