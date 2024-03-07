<div id="filter-users-modal" class="modal">
    <div class="modal-body w-full md:max-w-[800px]">

        <div class="modal-header">
            <div>
                <h2 id="filter-users-modal-title">Filtrar usuarios</h2>
            </div>

            <div>
                <button data-modal-id="filter-users-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>

        <div class="poa-form">

            <div class="grid md:grid-cols-2 grid-cols-1  gap-4">

                <div>
                    <div class="label-container label-center">
                        <label for="date_users">Fecha</label>
                    </div>
                    <div class="content-container mt-1">
                        <input type="datetime-local" class="poa-input" id="date_users"
                            name="date_users" />
                    </div>
                </div>

                <div>
                    <div class="label-container label-center">
                        <label for="roles_filter">Roles</label>
                    </div>

                    <div class="content-container mt-1">
                        <select id="roles_filter" class="mb-4" name="roles_filter[]" multiple
                            placeholder="Selecciona uno o varios roles">
                        </select>
                    </div>

                </div>
            </div>

            <div class="flex justify-center mt-8">
                <button id="filter-general-notifications-btn" type="button" class="btn btn-primary">
                    Filtrar {{ e_heroicon('adjustments-horizontal', 'outline') }}</button>
            </div>


        </div>

    </div>

</div>
