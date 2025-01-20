<div id="educational-resources-per-user-modal" class="modal">

    <div class="modal-body w-full md:max-w-[700px]">

        <div class="modal-header">
            <div>
                <h2 class="modal-title">Notificaciones vistas por usuario</h2>
            </div>

            <div>
                <button data-modal-id="educational-resources-per-user-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php eHeroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'educational-resources-per-user-table'])
        </div>

        <div class="table-container">
            <div id="educational-resources-per-user-table"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'educational-resources-per-user-table'])

    </div>

</div>
