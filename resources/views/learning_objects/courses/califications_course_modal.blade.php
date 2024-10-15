<div id="califications-course-modal" class="modal">
    <div class="modal-body w-full">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">Calificaciones de un curso</h2>
            </div>

            <div>
                <button data-modal-id="califications-course-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'califications-course-table'])
        </div>

        <div class="table-container">
            <div id="califications-course-table"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'califications-course-table'])

        <div class="btn-block">
            <button type="button" class="btn btn-primary" id="save-califications">Guardar</button>
        </div>

    </div>

</div>
