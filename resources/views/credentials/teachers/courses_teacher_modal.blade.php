<div id="courses-teacher-modal" class="modal">

    <div class="modal-body w-full md:max-w-[950px]">

        <div class="modal-header">
            <div>
                <h2 id="courses-teacher-modal-title">Listado de cursos del docente</h2>
            </div>

            <div>
                <button data-modal-id="courses-teacher-modal" class="modal-close-modal-btn close-modal-btn">

                    <?php eHeroicon('x-mark', 'solid'); ?>
                </button>
            </div>

        </div>

        <div class="modal-content">

            <div class="table-control-header">
                @include('partials.table-search', ['table' => 'courses-teacher-table'])

                <div class="flex gap-1">
                    <button id="generate-credentials-btn" type="button" class="btn-icon" title="Generar credenciales"
                        data-course-uid="">{{ eHeroicon('document-arrow-up', 'outline') }}</button>

                    <button id="emit-credentials-btn" type="button" class="btn-icon" title="Emitir credenciales"
                        data-course-uid="">{{ eHeroicon('academic-cap', 'outline') }}</button>

                    <button id="send-credentials-btn" type="button" class="btn-icon" title="Enviar credenciales"
                        data-course-uid="">{{ eHeroicon('paper-airplane', 'outline') }}</button>

                    <button id="seal-credentials-btn" type="button" class="btn-icon" title="Sellar credenciales"
                        data-course-uid="">{{ eHeroicon('check-badge', 'outline') }}</button>
                </div>

            </div>

            <div class="table-container">
                <div id="courses-teacher-table"></div>
            </div>

            @include('partials.table-pagination', ['table' => 'courses-teacher-table'])

        </div>
    </div>

    <input type="hidden" value="" id="user-uid">
</div>
