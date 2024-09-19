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
                        <input maxlength="255" placeholder="Marco de competencia" type="text" id="name"
                            name="name" class="poa-input" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container">
                        <label for="description">Descripción</label>
                    </div>

                    <div class="content-container">
                        <textarea maxlength="1000" placeholder="Descripción" rows="4" id="description" name="description" class="poa-input"></textarea>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="has_levels">¿Tiene varios niveles?</label>
                    </div>
                    <div class="content-container flex items-center">
                        <div class="checkbox">
                            <label for="has_levels" class="inline-flex relative items-center cursor-pointer">
                                <input type="checkbox" id="has_levels" name="has_levels" class="sr-only peer">
                                <div
                                    class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div id="level" class="hidden">
                    <div id="level" class="field">
                        <div class="label-container label-center">
                            <label for="level">Niveles</label>
                        </div>
                        <div class="content-container items-center">
                            <div id="level-list"></div>

                            <div class="flex justify-end">
                                <div>
                                    <button type="button" class="btn-icon" id="btn-add-level">
                                        {{ e_heroicon('plus', 'outline') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="btn-block">
                    <button type="submit" class="btn btn-primary">Guardar
                        {{ e_heroicon('paper-airplane', 'outline') }}</button>

                    <button data-modal-id="competence-framework-modal" type="button"
                        class="btn btn-secondary close-modal-btn">
                        Cancelar {{ e_heroicon('x-mark', 'outline') }}</button>
                </div>


                <input type="hidden" id="competence_framework_modal_uid" name="competence_framework_modal_uid" value="" />
            </form>
        </div>
    </div>

</div>

<template id="level-template">
    <div class="level mb-2">

        <div class="mb-2">
            <input type="text" class="poa-input level-name" placeholder="Nombre del nivel" />
        </div>

        <button class="w-[32px] h-[32px] btn-icon btn-remove-level" type="button">
            {{ e_heroicon('trash', 'outline') }}
        </button>

        <input type="hidden" class="level_uid" name="level_uid" value="" />
    </div>
</template>
