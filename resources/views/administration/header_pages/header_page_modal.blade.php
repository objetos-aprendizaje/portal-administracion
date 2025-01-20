<div id="header-page-modal" class="modal">

    <div class="modal-body w-full md:max-w-[950px]">

        <div class="modal-header">
            <div>
                <h2 class="modal-title">Página header</h2>
            </div>

            <div>
                <button data-modal-id="header-page-modal" class="modal-close-modal-btn close-modal-btn">

                    <?php eHeroicon('x-mark', 'solid'); ?>
                </button>
            </div>

        </div>


        <form id="header-page-form">
            @csrf

            <div class="poa-form">
                <div class="field">
                    <div class="label-container label-center">
                        <label for="name">Nombre <span class="text-red-500">*</span></label>
                    </div>
                    <div class="content-container">
                        <input maxlength="255" class="required poa-input" placeholder="Aviso legal" type="text" id="name"
                            name="name" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="slug">Slug <span class="text-red-500">*</span></label>
                    </div>
                    <div class="content-container">
                        <input maxlength="255" class="required poa-input" placeholder="aviso-legal" type="text" id="slug"
                            name="slug" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="name">Contenido <span class="text-red-500">*</span></label>
                    </div>
                    <div class="content-container">
                        <textarea maxlength="1000" id="header-page-content">Hello, World!</textarea>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="father_category">Página padre</label>
                    </div>

                    <div class="content-container">
                        <select id="parent_page_uid" name="parent_page_uid" class="poa-select w-full">
                            <option value="" selected>Ninguna</option>
                            @foreach ($pages as $page)
                                <option value="{{ $page['uid'] }}">{{ $page['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="order">Orden <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container">
                        <input placeholder="1" type="number" id="order" name="order" class="poa-input" />
                    </div>
                </div>


                <div class="flex justify-center mt-8 gap-4">

                    <button type="submit" value="submit" id="submit-button" class="btn btn-primary">
                        Guardar {{ eHeroicon('paper-airplane', 'outline') }}</button>

                    <button data-modal-id="header-page-modal" type="button"
                        class="btn btn-secondary close-modal-btn">Cancelar
                        {{ eHeroicon('x-mark', 'outline') }}</button>
                </div>

            </div>

            <input type="hidden" id="header_page_uid" name="header_page_uid" />

        </form>

    </div>

</div>
