<div id="enroll-course-modal" class="modal">
    <div class="modal-body w-full md:max-w-[800px]">
        <div class="modal-header">
            <div>
                <h2 id="enroll-course-modal-title">Asignar alumnos al curso</h2>
            </div>

            <div>
                <button data-modal-id="enroll-course-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php eHeroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>


        <div class="poa-form">

            <div class="grid grid-cols-1  gap-4">

                <div class="field-col ">
                    <div class="label-container label-center">
                        <label for="enroll_students">Alumnos</label>
                    </div>

                    <div class="content-container mt-1">
                        <select id="enroll_students" class="mb-4" name="enroll_students[]" multiple
                            placeholder="Selecciona alumnos..." autocomplete="off">

                        </select>
                    </div>

                </div>

            </div>
            <div class="flex justify-center mt-8">
                <button id="enroll-btn" type="button" class="btn btn-primary">
                    Añadir {{ eHeroicon('plus', 'outline') }}</button>
            </div>

        </div>

    </div>

</div>
