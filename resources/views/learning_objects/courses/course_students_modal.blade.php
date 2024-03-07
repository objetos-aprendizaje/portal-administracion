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


                <div class="flex gap-2">
                    <div>
                        <button id="approve-students-btn" type="button" class="btn-icon"
                            data-course-uid="">{{ e_heroicon('check', 'outline') }}</button>
                    </div>

                    <div>
                        <button id="reject-students-btn" type="button" class="btn-icon"
                            data-course-uid="">{{ e_heroicon('x-mark', 'outline') }}</button>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <div id="course-students-table"></div>
            </div>

            @include('partials.table-pagination', ['table' => 'course-students-table'])


        </div>


    </div>

</div>
