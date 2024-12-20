<div id="educational-program-students-modal" class="modal">
    <div class="modal-body w-full md:max-w-[1200px]">
        <div class="modal-header">
            <div class="">
                <h2 id="educational-program-modal-title" class="modal-title">Gestión de estudiantes</h2>
            </div>

            <div>
                <button data-modal-id="educational-program-students-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>

            </div>
        </div>

        <div>
            <div class="table-control-header">
                @include('partials.table-search', ['table' => 'educational-program-students-table'])

                <div class="flex gap-1">
                    <button id="approve-students-btn" type="button" class="btn-icon" title="Aprobar estudiantes"
                        data-educational-program-uid="">{{ e_heroicon('check', 'outline') }}</button>

                    <button id="reject-students-btn" type="button" class="btn-icon" title="Rechazar estudiantes"
                        data-educational-program-uid="">{{ e_heroicon('x-mark', 'outline') }}</button>

                    <button id="delete-students-btn" type="button" class="btn-icon" title="Eliminar estudiantes"
                        data-educational-program-uid="">{{ e_heroicon('trash', 'outline') }}</button>

                    <button id="enroll-students-btn" type="button" class="btn-icon" title="Asignar estudiantes"
                        data-educational-program-uid="">{{ e_heroicon('plus', 'outline') }}</button>

                    <button id="enroll-students-csv-btn" type="button" class="btn-icon" title="Asignar estudiantes desde CSV"
                        data-educational-program-uid="">{{ e_heroicon('arrow-up-tray', 'outline') }}</button>

                    <button id="emit-credentials-students-btn" type="button" class="btn-icon"
                        title="Emitir credenciales"
                        data-course-uid="">{{ e_heroicon('academic-cap', 'outline') }}</button>

                    <button id="send-credentials-btn" type="button" class="btn-icon" title="Enviar credenciales"
                        data-course-uid="">{{ e_heroicon('paper-airplane', 'outline') }}</button>

                    <button id="seal-credentials-btn" type="button" class="btn-icon" title="Sellar credenciales"
                        data-course-uid="">{{ e_heroicon('check-badge', 'outline') }}</button>
                </div>
            </div>

            <div class="table-container">
                <div id="educational-program-students-table"></div>
            </div>

            @include('partials.table-pagination', ['table' => 'educational-program-students-table'])


        </div>


    </div>

</div>
