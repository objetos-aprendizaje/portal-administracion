<div id="certification-type-modal" class="modal">

    <div class="modal-body w-full md:max-w-[700px]">

        <div class="modal-header">
            <div>
                <h2 id="certification-type-modal-title" class="modal-title">Añade un nuevo tipo de certificación</h2>
            </div>

            <div>
                <button data-modal-id="certification-type-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>

        <form id="certification-type-form">
            @csrf

            <div class="poa-form">

                <div class="field">
                    <div class="label-container label-center">
                        <label for="name">Nombre <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container">
                        <input maxlength="255" type="text" id="name" name="name" class="poa-input" />
                    </div>
                </div>

                <div class="field mt-2">
                    <div class="label-container label-center">
                        <label for="category_uid">Categoría <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container mt-1">
                        <select class="poa-select w-full" id="category_uid" name="category_uid">
                            <option value="" selected>Ninguna</option>
                            @include('cataloging.certification_types.categories_options', ['categories' => $categories, 'level' => 0])
                        </select>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container">
                        <label for="description">Descripción</label>
                    </div>
                    <div class="content-container">
                        <textarea id="description" name="description" class="poa-input"></textarea>
                    </div>
                </div>

                <input type="hidden" id="certification_type_uid" name="certification_type_uid" value="" />

                <div class="btn-block">
                    <button type="submit" class="btn btn-primary">Guardar
                        {{ e_heroicon('paper-airplane', 'outline') }}
                    </button>

                    <button data-modal-id="certification-type-modal" type="button"
                        class="btn btn-secondary close-modal-btn">Cancelar
                        {{ e_heroicon('x-mark', 'outline') }}</button>
                </div>

            </div>

        </form>
    </div>

</div>
