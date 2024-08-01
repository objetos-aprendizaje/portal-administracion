<div id="competence-framework-modal" class="modal">

    <div class="modal-body w-full md:max-w-[900px]">

        <div class="modal-header">
            <div>
                <h2 class="modal-title">Nuevo marco de competencias</h2>
            </div>

            <div>
                <button data-modal-id="competence-framework-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>

        <div class="poa-form mt-6">

            <form id="competence-framework-form">
                <div class="field">
                    <div class="label-container label-center">
                        <label for="name">Nombre <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container">
                        <input maxlength="255" placeholder="Marco de competencia" type="text" id="name" name="name"
                            class="poa-input" />
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

                <div class="type-competence">
                    <div class="field">
                        <div class="label-container label-center">
                            <label for="type">Tipo</label>
                        </div>
                        <div class="content-container">
                            <input maxlength="255" placeholder="Tipo" type="text" id="type" name="type"
                                class="poa-input" />
                        </div>
                    </div>
                </div>

                <div id="is-multi-select-container">
                    <div class="field">
                        <div class="label-container label-center">
                            <label for="is_multi_select">Permite seleccionar varios resultados de aprendizaje <span
                                    class="text-danger">*</span></label>
                        </div>
                        <div class="content-container">
                            <select id="is_multi_select" name="is_multi_select" class="poa-select w-full">
                                <option value="">Seleccione una opción</option>
                                <option value="1" selected>Sí</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                    </div>
                </div>


                <div class="btn-block">
                    <button type="submit" class="btn btn-primary">Guardar
                        {{ e_heroicon('paper-airplane', 'outline') }}</button>

                    <button data-modal-id="competence-framework-modal" type="button" class="btn btn-secondary close-modal-btn">
                        Cancelar {{ e_heroicon('x-mark', 'outline') }}</button>
                </div>

                <input type="hidden" id="competence_uid" name="competence_uid" value="" />
                <input type="hidden" id="parent_competence_uid" name="parent_competence_uid" value="" />

            </form>
        </div>
    </div>

</div>
