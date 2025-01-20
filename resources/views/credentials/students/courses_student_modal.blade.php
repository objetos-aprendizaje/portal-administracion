<div id="courses-student-modal" class="modal">

    <div class="modal-body w-full md:max-w-[950px]">

        <div class="modal-header">
            <div>
                <h2 id="courses-student-modal-title">Listado de estudiantes</h2>
            </div>

            <div>
                <button data-modal-id="courses-student-modal" class="modal-close-modal-btn close-modal-btn">

                    <?php eHeroicon('x-mark', 'solid'); ?>
                </button>
            </div>

        </div>

        <div class="modal-content">

            <div class="table-control-header">
                @include('partials.table-search', ['table' => 'courses-student-table'])
                <div class="flex gap-1">

                    <button id="emit-credentials-btn" type="button" class="btn-icon" title="Emitir credenciales"
                        data-course-uid="">{{ eHeroicon('academic-cap', 'outline') }}</button>

                    <button id="send-credentials-btn" type="button" class="btn-icon" title="Enviar credenciales"
                        data-course-uid="">{{ eHeroicon('paper-airplane', 'outline') }}</button>

                    <button id="seal-credentials-btn" type="button" class="btn-icon" title="Sellar credenciales"
                        data-course-uid="">{{ eHeroicon('check-badge', 'outline') }}</button>

                </div>
            </div>

            <div class="table-container">
                <div id="courses-student-table"></div>
            </div>

            @include('partials.table-pagination', ['table' => 'courses-student-table'])

        </div>
    </div>

    <input type="hidden" id="user-uid" value=""/>
</div>
