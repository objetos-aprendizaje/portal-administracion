<div id="educational-program-students-modal" class="modal">
    <div class="modal-body w-full md:max-w-[1200px]">
        <div class="modal-header">
            <div class="">
                <h2 id="educational-program-modal-title" class="modal-title">Gestión de estudiantes</h2>
            </div>

            <div>
                <button data-modal-id="educational-program-students-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php eHeroicon('x-mark', 'solid'); ?>
                </button>

            </div>
        </div>

        <div>
            <div class="table-control-header">
                @include('partials.table-search', ['table' => 'educational-program-students-table'])

                <div class="flex gap-1">
                    <button id="approve-students-btn" type="button" class="btn-icon" title="Aprobar estudiantes"
                        data-educational-program-uid="">{{ eHeroicon('check', 'outline') }}</button>

                    <button id="reject-students-btn" type="button" class="btn-icon" title="Rechazar estudiantes"
                        data-educational-program-uid="">{{ eHeroicon('x-mark', 'outline') }}</button>

                    <button id="delete-students-btn" type="button" class="btn-icon" title="Eliminar estudiantes"
                        data-educational-program-uid="">{{ eHeroicon('trash', 'outline') }}</button>

                    <button id="enroll-students-btn" type="button" class="btn-icon" title="Asignar estudiantes"
                        data-educational-program-uid="">{{ eHeroicon('plus', 'outline') }}</button>

                    <button id="enroll-students-csv-btn" type="button" class="btn-icon" title="Asignar estudiantes desde CSV"
                        data-educational-program-uid="">{{ eHeroicon('arrow-up-tray', 'outline') }}</button>

                    <button id="emit-credentials-students-btn" type="button" class="btn-icon"
                        title="Emitir credenciales"
                        data-course-uid="">{{ eHeroicon('academic-cap', 'outline') }}</button>

                    <button id="send-credentials-btn" type="button" class="btn-icon" title="Enviar credenciales"
                        data-course-uid="">{{ eHeroicon('paper-airplane', 'outline') }}</button>

                    <button id="seal-credentials-btn" type="button" class="btn-icon" title="Sellar credenciales"
                        data-course-uid="">{{ eHeroicon('check-badge', 'outline') }}</button>
                </div>
            </div>

            <div class="table-container">
                <div id="educational-program-students-table"></div>
            </div>

            @include('partials.table-pagination', ['table' => 'educational-program-students-table'])


        </div>


    </div>

</div>
