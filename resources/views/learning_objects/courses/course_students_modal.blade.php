<div id="course-students-modal" class="modal">
    <div class="modal-body w-full md:max-w-[1200px]">
        <div class="modal-header">
            <div class="">
                <h2 id="course-modal-title" class="modal-title">Gestión de estudiantes</h2>
            </div>

            <div>
                <button data-modal-id="course-students-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php eHeroicon('x-mark', 'solid'); ?>
                </button>

            </div>
        </div>

        <div>
            <div class="table-control-header">
                @include('partials.table-search', ['table' => 'course-students-table'])
                <div class="flex gap-1">
                    <button id="approve-students-btn" type="button" class="btn-icon" title="Aprobar"
                        data-course-uid="">{{ eHeroicon('check', 'outline') }}</button>

                    <button id="reject-students-btn" type="button" class="btn-icon" title="Rechazar"
                        data-course-uid="">{{ eHeroicon('x-mark', 'outline') }}</button>

                    <button id="delete-students-btn" type="button" class="btn-icon" data-educational-program-uid=""
                        title="Eliminar">{{ eHeroicon('trash', 'outline') }}</button>

                    <button id="enroll-students-btn" type="button" class="btn-icon" title="Añadir estudiante"
                        data-course-uid="">{{ eHeroicon('plus', 'outline') }}</button>

                    <button id="enroll-students-csv-btn" type="button" class="btn-icon" title="Importar estudiantes"
                        data-course-uid="">{{ eHeroicon('arrow-up-tray', 'outline') }}</button>

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
                <div id="course-students-table"></div>
            </div>

            @include('partials.table-pagination', ['table' => 'course-students-table'])


        </div>


    </div>

</div>
