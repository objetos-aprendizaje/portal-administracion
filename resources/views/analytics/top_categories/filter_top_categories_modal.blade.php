<div id="filter-top-categories-modal" class="modal">
    <div class="modal-body w-full md:max-w-[800px]">
        <div class="modal-header">
            <div>
                <h2 id="filter-top-categories-modal-title">Filtrar TOP categorías</h2>
            </div>

            <div>
                <button data-modal-id="filter-top-categories-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>

        <div class="poa-form">
            <p class="mb-4">Filtra la relación entre alumnos e inscripciones en los cursos.</p>

            <div class="grid md:grid-cols-2 grid-cols-1  gap-4">

                <div>
                    <div class="label-container label-center">
                        <label for="filter_acceptance_status">Estados de aceptación del alumno</label>
                    </div>

                    <div class="content-container mt-1">
                        <select id="filter_acceptance_status" class="mb-4" name="filter_acceptance_status[]" multiple
                            placeholder="Selecciona estados de aceptación..." autocomplete="off">
                            <option value="ACCEPTED">Aceptado</option>
                            <option value="PENDING">Pendiente</option>
                            <option value="REJECTED">Rechazado</option>
                        </select>
                    </div>
                </div>

                <div>
                    <div class="label-container label-center">
                        <label for="filter_status">Estados del alumno</label>
                    </div>

                    <div class="content-container mt-1">
                        <select id="filter_status" class="mb-4" name="filter_status[]" multiple
                            placeholder="Selecciona estados del alumno..." autocomplete="off">
                            <option value="ENROLLED">Matriculado</option>
                            <option value="INSCRIBED">Inscrito</option>
                        </select>
                    </div>
                </div>

                <div>
                    <div class="label-container label-center">
                        <label for="filter_created_date">Fecha asociación al curso</label>
                    </div>
                    <div class="content-container mt-1">
                        <input type="datetime-local" placeholder="Selecciona un rango de fechas" class="poa-input"
                            id="filter_created_date" name="filter_created_date" />
                    </div>
                </div>

            </div>

            <div class="flex justify-center mt-8">
                <button id="filter-btn" type="button" class="btn btn-primary">
                    Filtrar {{ e_heroicon('adjustments-horizontal', 'outline') }}</button>
            </div>
        </div>

    </div>

</div>
