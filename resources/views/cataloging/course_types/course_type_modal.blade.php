<div id="course-type-modal" class="modal">

    <div class="modal-body w-full md:max-w-[700px]">

        <div class="modal-header">
            <div>
                <h2 class="modal-title">Añade un nuevo tipo de curso</h2>
            </div>

            <div>
                <button data-modal-id="course-type-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>

        <form id="course-type-form">
            @csrf

            <div class="poa-form">

                <div class="field">
                    <div class="label-container label-center">
                        <label for="name">Nombre <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container">
                        <input type="text" id="name" name="name" class="poa-input" placeholder="MOOC"/>
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

                <input type="hidden" id="course_type_uid" name="course_type_uid" value="" />

                <div class="btn-block">
                    <button type="submit" class="btn btn-primary">Guardar</button>

                    <button data-modal-id="course-type-modal" type="button"
                        class="btn btn-secondary close-modal-btn">Cancelar {{ e_heroicon('x-mark', 'outline') }}</button>
                </div>

            </div>

        </form>
    </div>

</div>
