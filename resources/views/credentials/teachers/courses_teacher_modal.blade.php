<div id="courses-teacher-modal" class="modal">

    <div class="modal-body w-full md:max-w-[950px]">

        <div class="modal-header">
            <div>
                <h2 id="courses-teacher-modal-title"></h2>
            </div>

            <div>
                <button data-modal-id="courses-teacher-modal" class="modal-close-modal-btn close-modal-btn">

                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>
            </div>

        </div>

        <div class="modal-content">

            <div class="table-control-header">
                @include('partials.table-search', ['table' => 'courses-teacher-table'])
            </div>

            <div class="table-container">
                <div id="courses-teacher-table"></div>
            </div>

            @include('partials.table-pagination', ['table' => 'courses-teacher-table'])

        </div>
    </div>

</div>
