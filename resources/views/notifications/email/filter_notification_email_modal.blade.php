<div id="filter-notification-email-modal" class="modal">
    <div class="modal-body w-full md:max-w-[800px]">
        <div class="modal-header">
            <div>
                <h2 id="filter-notification-email-modal-title">Filtrar notificaciones por email</h2>
            </div>

            <div>
                <button data-modal-id="filter-notification-email-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>

        <div class="poa-form">

            <div class="grid md:grid-cols-2 grid-cols-1  gap-4 mb-4">

                <div>
                    <div class="label-container label-center">
                        <label for="state_email_notification">Estado de la notificación</label>
                    </div>
                    <div class="content-container mt-1">
                        <select id="state_email_notification" class="poa-select w-full" name="state_email_notification"
                            placeholder="Selecciona un estado">
                            <option value="" selected></option>
                            <option value="1">Enviado</option>
                            <option value="0">No enviado</option>
                        </select>
                    </div>
                </div>

                <div>
                    <div class="label-container label-center">
                        <label for="date_email_notifications">Fecha de Envío</label>
                    </div>
                    <div class="content-container mt-1">
                        <input type="datetime-local" placeholder="Selecciona un rango de fechas" class="poa-input" id="date_email_notifications"
                            name="date_email_notifications" />
                    </div>
                </div>

                <div>
                    <div class="label-container label-center">
                        <label for="roles_filter">Tipos de notificación</label>
                    </div>

                    <div class="content-container mt-1">
                        <select id="notification_types" class="mb-4" name="notification_types[]" multiple
                            placeholder="Selecciona uno o varios tipos de notificaciones">
                            @foreach ($notification_types as $notification_type)
                                <option value="{{ $notification_type['uid'] }}">{{ $notification_type['name'] }}</option>
                            @endforeach
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
