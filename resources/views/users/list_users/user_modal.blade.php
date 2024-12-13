<div id="user-modal" class="modal">

    <div class="modal-body w-full md:max-w-[900px]">

        <div class="modal-header">
            <div>
                <h2 class="modal-title">Añade un nuevo usuario </h2>
            </div>

            <div>
                <button data-modal-id="user-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>

        <form id="user-form" enctype="multipart/form-data">
            @csrf

            <div class="poa-form">
                <div class="field">
                    <div class="label-container label-center">
                        <label for="first_name">Nombre <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container">
                        <input maxlength="100" placeholder="Manuel" type="text" id="first_name" name="first_name"
                            class="poa-input" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="last_name">Apellidos <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container">
                        <input maxlength="255" placeholder="Pérez Martínez" type="text" id="last_name"
                            name="last_name" class="poa-input" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="nif">NIF</label>
                    </div>
                    <div class="content-container">
                        <input maxlength="255" placeholder="12345678X" type="text" id="nif" name="nif"
                            class="poa-input" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="email">Email <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container">
                        <input maxlength="150" placeholder="email@email.com" type="text" id="email"
                            name="email" class="poa-input" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="user_rol_uid">Roles <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container">
                        <select id="roles" name="roles" name="roles[]" multiple>
                            @foreach ($userRoles as $userRol)
                                <option value="{{ $userRol->uid }}">{{ $userRol->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="department_uid">Departamento</label>
                    </div>
                    <div class="content-container">
                        <select id="department_uid" name="department_uid" class="poa-select w-full">
                            <option value="">Selecciona un departamento</option>

                            @foreach ($departments as $department)
                                <option value="{{ $department->uid }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container">
                        <label for="curriculum">Currículum</label>
                    </div>
                    <div class="content-container">
                        <textarea rows="5" class="poa-input" id="curriculum" name="curriculum"></textarea>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container">
                        <label for="image_input_file">Foto</label>
                    </div>
                    <div class="content-container">

                        <div class="poa-input-image">
                            <img id="photo_path_preview" src="{{ env('NO_IMAGE_SELECTED_PATH') }}" />

                            <span class="dimensions">*Se recomienda imagen con aspecto cuadrado con una resolución
                                mínima de: 400px x 400px.
                                Formato: PNG, JPG. Tam. Máx.: 6MB</span>

                            <div class="select-file-container">
                                <input accept="image/*" type="file" id="photo_path" name="photo_path"
                                    class="hidden" />

                                <label for="photo_path" class="btn btn-rectangular w-[110px]">
                                    Subir {{ e_heroicon('arrow-up-tray', 'outline') }}
                                </label>

                                <span id="image-name" class="image-name text-[14px]">Ningún archivo seleccionado</span>
                            </div>

                        </div>
                    </div>

                </div>

                <input type="hidden" id="user_uid" name="user_uid" value="" />

                <div class="btn-block">
                    <button type="submit" class="btn btn-primary">
                        Guardar {{ e_heroicon('paper-airplane', 'outline') }}</button>

                    <button data-modal-id="user-modal" type="button"
                        class="btn btn-secondary close-modal-btn btn-close-modal-user">Cancelar
                        {{ e_heroicon('x-mark', 'outline') }}</button>
                </div>

            </div>

        </form>
    </div>

</div>
