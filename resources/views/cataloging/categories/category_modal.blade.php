<div id="category-modal" class="modal">

    <div class="modal-body w-full md:max-w-[900px]">

        <div class="modal-header">
            <div>
                <h2 class="modal-title">Nueva categoría</h2>
            </div>

            <div>
                <button data-modal-id="category-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>

        <div class="poa-form mt-6">

            <form id="category-form">
                <div class="field">
                    <div class="label-container label-center">
                        <label for="name">Nombre <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container">
                        <input placeholder="Categoría" type="text" id="name" name="name" class="poa-input" />
                    </div>
                </div>

                <div class="field">

                    <div class="label-container">
                        <label for="description">Descripción</label>
                    </div>

                    <div class="content-container">
                        <textarea placeholder="Descripción" rows="4" id="description" name="description" class="poa-input"></textarea>
                    </div>

                </div>

                <div class="field">
                    <div class="label-container">
                        <label for="image_path">Imagen <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container">
                        <div class="poa-input-image">
                            <img id="image_path_preview" src="{{ env('NO_IMAGE_SELECTED_PATH') }}" />
                            <span class="dimensions">*Se recomienda imagen con aspecto cuadrado con una resolución mínima de: 200px x 200px.
                                Formato: PNG, JPG. Tam. Máx.: 6MB</span>

                            <div class="select-file-container">
                                <input accept="image/*" type="file" id="image_path" name="image_path"
                                    class="hidden" />

                                <label for="image_path" class="btn  btn-rectangular">
                                    Subir <span>{{ e_heroicon('arrow-up-tray', 'outline') }}</span>
                                </label>

                                <span id="image-name" class="image-name text-[14px]">Ningún archivo seleccionado</span>

                            </div>

                        </div>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container">
                        <label for="color">Color <span class="text-danger">*</span></label>
                    </div>

                    <div class="content-container">
                        <div class="coloris-input">
                            <input value="#fff" class="poa-input" type="text" data-coloris id="color"
                                name="color">
                        </div>
                    </div>

                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="father_category">Categoría padre</label>
                    </div>

                    <div class="content-container">
                        <select id="parent_category_uid" name="parent_category_uid" class="poa-select w-full">
                            <option value="" selected>Ninguna</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category['uid'] }}">{{ $category['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="btn-block">
                    <button type="submit" class="btn btn-primary">Guardar
                        {{ e_heroicon('paper-airplane', 'outline') }}</button>

                    <button data-modal-id="category-modal" type="button" class="btn btn-secondary close-modal-btn">
                        Cancelar {{ e_heroicon('x-mark', 'outline') }}</button>
                </div>

                <input type="hidden" id="category_uid" name="category_uid" value="" />

            </form>
        </div>
    </div>

</div>
