<div id="filter-general-notifications-modal" class="modal">
    <div class="modal-body w-full md:max-w-[800px]">
        <div class="modal-header">
            <div>
                <h2 id="filter-general-notifications-modal-title">Filtrar notificaciones generales</h2>
            </div>

            <div>
                <button data-modal-id="filter-general-notifications-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php eHeroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>

        <div class="poa-form">

            <div class="gap-4 mb-4 flex-col grid md:grid-cols-2 grid-cols-1">

                <div class="mb-4">
                    <div class="label-container label-center">
                        <label for="type-filter">Para <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container">
                        <select class="poa-select w-full" id="type-filter" name="type-filter">
                            <option value="">Selecciona hacia quién va a ir dirigida</option>
                            <option value="ALL_USERS">Todos los usuarios</option>
                            <option value="ROLES">Conjunto de roles</option>
                            <option value="USERS">Usuarios concretos</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4 no-visible" id="destination-roles-filter">
                    <div class="label-container label-center">
                        <label for="roles-filter">Destino <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container">
                        <select id="roles-filter" class="mb-4" name="roles[]" multiple placeholder="Selecciona uno o varios roles">
                        </select>
                    </div>
                </div>

                <div class="mb-4  no-visible" id="destination-users-filter">
                    <div class="label-container label-center">
                        <label for="users-filter">Destino <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container">
                        <select id="users-filter" class="mb-4" name="users[]" multiple placeholder="Selecciona uno o varios usuarios">
                        </select>
                    </div>
                </div>

            </div>

            <div class="gap-4 mb-4 flex-col grid md:grid-cols-2 grid-cols-1">

                <div class="mb-4">
                    <div class="label-container label-center">
                        <label for="date_notifications">Fecha de inicio</label>
                    </div>
                    <div class="content-container mt-1">
                        <input type="datetime-local" class="poa-input" id="start_date_filter"
                            name="start_date_filter" />
                    </div>
                </div>

                <div class="mb-4">
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
                Filtrar {{ eHeroicon('adjustments-horizontal', 'outline') }}</button>
        </div>
    </div>
</div>

</div>
