<div id="list-users-views-general-notification-modal" class="modal">

    <div class="modal-body w-full md:max-w-[900px]">

        <div class="modal-header">
            <div>
                <h2 id="list-users-views-general-notification-modal-title">Usuarios que han visto la notificación</h2>
            </div>
            <div>
                <button data-modal-id="list-users-views-general-notification-modal"
                    class="modal-close-modal-btn close-modal-btn">
                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'list-user-views-general-notification-table'])

        </div>

        <div class="table-container">
            <div id="list-user-views-general-notification-table"></div>
        </div>

        <div class="list-users-views-general-notification">
            @include('partials.table-pagination', ['table' => 'list-user-views-general-notification-table'])
        </div>

    </div>

</div>
