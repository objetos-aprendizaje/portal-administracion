<div id="notification-email-modal" class="modal">

    <div class="modal-body w-full md:max-w-[700px]">

        <div class="modal-header">
            <div>
                <h2 class="modal-title">Envía una notificación por email</h2>
            </div>
            <div>
                <button data-modal-id="notification-email-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php eHeroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>

        <form id="notification-email-form">
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
                            <option value="">Selecciona el tipo de notificación</option>
                            @foreach ($notification_types as $notification_type)
                                <option value="{{ $notification_type->uid }}">
                                    {{ $notification_type->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="send_date">Fecha de envío</label>
                    </div>
                    <div class="content-container">
                        <input type="datetime-local" id="send_date" name="send_date" class="poa-input" />
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
                        <label for="subject">Asunto <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container">
                        <input maxlength="255" type="text" id="subject" name="subject" class="poa-input" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container">
                        <label for="body">Cuerpo <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container">
                        <textarea maxlength="1000" id="body" name="body" class="poa-input"></textarea>
                    </div>
                </div>


                <input type="hidden" id="notification_email_uid" name="notification_email_uid" value="" />

                <div class="btn-block" id="email-notification-modal-add-btns">
                    <button type="submit" class="btn btn-primary">
                        Guardar {{ eHeroicon('paper-airplane', 'outline') }}</button>

                    <button data-modal-id="notification-email-modal" type="button"
                        class="btn btn-secondary close-modal-btn btn-close-modal-notification-email">Cancelar
                        {{ eHeroicon('x-mark', 'outline') }}</button>
                </div>

                <div class="btn-block" id="email-notification-modal-view-btns">
                    <button data-modal-id="notification-email-modal" type="button"
                        class="btn btn-secondary close-modal-btn btn-close-modal-notification-email">Cerrar
                        {{ eHeroicon('x-mark', 'outline') }}</button>
                </div>

            </div>

        </form>
    </div>

</div>
