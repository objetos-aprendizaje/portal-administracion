<div id="notification-general-modal" class="modal">

    <div class="modal-body w-full md:max-w-[700px]">

        <div class="modal-header">
            <div>
                <h2 class="modal-title">Añade un nueva notificación general</h2>
            </div>
            <div>
                <button data-modal-id="notification-general-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php eHeroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>

        <form id="notification-general-form">
            @csrf

            <div class="poa-form">

                <div class="field">
                    <div class="label-container label-center">
                        <label for="type">Para <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container">
                        <select class="poa-select w-full" id="type" name="type">
                            <option value="">Selecciona hacia quién va a ir dirigida</option>
                            <option value="ALL_USERS">Todos los usuarios</option>
                            <option value="ROLES">Conjunto de roles</option>
                            <option value="USERS">Usuarios concretos</option>
                        </select>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="notification_type_uid">Tipo <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container">
                        <select class="poa-select w-full" id="notification_type_uid" name="notification_type_uid">
                            <option value="">Ninguna</option>
                            @foreach ($notification_types as $notification_type)
                                <option value="{{ $notification_type->uid }}">
                                    {{ $notification_type->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="field no-visible" id="destination-roles">
                    <div class="label-container label-center">
                        <label for="roles">Destino <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container">
                        <select id="roles" class="mb-4" name="roles[]" multiple placeholder="Selecciona uno o varios roles">
                        </select>
                    </div>
                </div>

                <div class="field no-visible" id="destination-users">
                    <div class="label-container label-center">
                        <label for="users">Destino <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container">
                        <select id="users" class="mb-4" name="users[]" multiple placeholder="Selecciona uno o varios usuarios">
                        </select>
                    </div>
                </div>
                <div class="field">
                    <div class="label-container label-center">
                        <label for="title">Titulo <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container">
                        <input maxlength="100" type="text" id="title" name="title" class="poa-input" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container">
                        <label for="description">Descripción <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container">
                        <textarea maxlength="1000" id="description" name="description" class="poa-input"></textarea>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="start_date">Fecha de inicio <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container">
                        <input type="datetime-local" id="start_date" name="start_date" class="poa-input" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="end_date">Fecha de fin <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container">
                        <input type="datetime-local" id="end_date" name="end_date" class="poa-input" />
                    </div>
                </div>

                <input type="hidden" id="notification_general_uid" name="notification_general_uid" value="" />

                <div class="btn-block">
                    <button type="submit" class="btn btn-primary">
                        Guardar {{ eHeroicon('paper-airplane', 'outline') }}</button>

                    <button data-modal-id="notification-general-modal" type="button"
                        class="btn btn-secondary close-modal-btn btn-close-modal-notification-general">Cancelar
                        {{ eHeroicon('x-mark', 'outline') }}</button>
                </div>

            </div>

        </form>
    </div>

</div>
