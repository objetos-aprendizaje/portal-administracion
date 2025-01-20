<div id="filter-logs-modal" class="modal">
    <div class="modal-body w-full md:max-w-[800px]">
        <div class="modal-header">
            <div>
                <h2 id="filter-logs-modal-title">Filtrar logs</h2>
            </div>

            <div>
                <button data-modal-id="filter-logs-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php eHeroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>


        <div class="poa-form">

            <div class="grid grid-cols-1  gap-4">

                <div class="field-col ">
                    <div class="label-container label-center">
                        <label for="filter_entities">Entidad</label>
                    </div>

                    <div class="content-container mt-1">
                        <select id="filter_entities" class="mb-4" name="entities[]" multiple
                            placeholder="Selecciona entidad..." autocomplete="off">
                            @foreach ($entities as $entity)
                                <option value="{{ $entity['uid'] }}">
                                    {{ $entity['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>

                <div>
                    <div class="label-container label-center">
                        <label for="filter_users">Usuarios</label>
                    </div>

                    <div class="content-container mt-1">
                        <select id="filter_users" class="mb-4" name="users[]" multiple
                            placeholder="Selecciona un usuario..." autocomplete="off">
                            @foreach ($users as $user)
                                <option value="{{ $user['uid'] }}">
                                    {{ $user['first_name'] }} {{ $user['last_name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <div class="label-container label-center">
                        <label for="filter_date">Fecha</label>
                    </div>
                    <div class="content-container mt-1">
                        <input type="datetime-local" placeholder="Selecciona un rango de fechas" class="poa-input" id="filter_date"
                            name="filter_date" />
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
