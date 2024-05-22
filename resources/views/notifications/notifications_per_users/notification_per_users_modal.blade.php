<div id="notifications-per-user-modal" class="modal">

    <div class="modal-body w-full md:max-w-[700px]">

        <div class="modal-header">
            <div>
                <h2 class="modal-title">Notificaciones vistas por usuario</h2>
            </div>

            <div>
                <button data-modal-id="notifications-per-user-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'notifications-per-user-table'])
        </div>

        <div class="table-container">
            <div id="notifications-per-user-table"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'notifications-per-user-table'])

    </div>

</div>
