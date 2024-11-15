<div id="course-students-modal" class="modal">
    <div class="modal-body w-full md:max-w-[1200px]">
        <div class="modal-header">
            <div class="">
                <h2 id="course-modal-title" class="modal-title">Gestión de estudiantes</h2>
            </div>

            <div>
                <button data-modal-id="course-students-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>

            </div>
        </div>

        <div>
            <div class="table-control-header">
                @include('partials.table-search', ['table' => 'course-students-table'])
                <div class="flex gap-1">
                    <button id="approve-students-btn" type="button" class="btn-icon" title="Aprobar"
                        data-course-uid="">{{ e_heroicon('check', 'outline') }}</button>

                    <button id="reject-students-btn" type="button" class="btn-icon" title="Rechazar"
                        data-course-uid="">{{ e_heroicon('x-mark', 'outline') }}</button>

                    <button id="delete-students-btn" type="button" class="btn-icon"
                        data-educational-program-uid="">{{ e_heroicon('trash', 'outline') }}</button>

                    <button id="enroll-students-btn" type="button" class="btn-icon" title="Añadir estudiante"
                        data-course-uid="">{{ e_heroicon('plus', 'outline') }}</button>

                    <button id="enroll-students-csv-btn" type="button" class="btn-icon" title="Importar estudiantes"
                        data-course-uid="">{{ e_heroicon('arrow-up-tray', 'outline') }}</button>

                    <button id="send-credentials-students-btn" type="button" class="btn-icon"
                        title="Enviar credenciales"
                        data-course-uid="">{{ e_heroicon('academic-cap', 'outline') }}</button>
                </div>
            </div>

            <div class="table-container">
                <div id="course-students-table"></div>
            </div>

            @include('partials.table-pagination', ['table' => 'course-students-table'])


        </div>


    </div>

</div>
