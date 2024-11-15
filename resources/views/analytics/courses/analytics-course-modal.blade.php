<div id="analytics-course-modal" class="modal">

    <div class="modal-body w-full md:max-w-[1000px]">

        <div class="modal-header">
            <div>
                <h2 id="analytics-course-modal-title" class="modal-title"></h2>
            </div>
            <div>
                <button data-modal-id="analytics-course-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>

        <div>
            <form id="course-form" enctype="multipart/form-data" class="mb-12">
                @csrf

                <div class="poa-form">

                    <div class="field mt-2">
                        <div class="label-container label-center">
                            <label for="last_access">Último acceso al curso:</label>
                        </div>
                        <div class="content-container mt-1">
                            <div id="last_access"></div>
                        </div>
                    </div>
                    <div class="field mt-2">
                        <div class="label-container label-center">
                            <label for="last_visit">Última visita al curso: </label>
                        </div>
                        <div class="content-container mt-1">
                            <div id="last_visit"></div>
                        </div>
                    </div>
                    <div class="field mt-2">
                        <div class="label-container label-center">
                            <label for="unique_users">Accesos al curso:</label>
                        </div>
                        <div class="content-container mt-1">
                            <div id="unique_users"></div>
                        </div>
                    </div>
                    <div class="field mt-2">
                        <div class="label-container label-center">
                            <label for="insribed_users">Usuarios inscritos: </label>
                        </div>
                        <div class="content-container mt-1">
                            <div id="insribed_users"></div>
                        </div>
                    </div>

                    <div class="field mt-2">
                        <div class="label-container label-center">
                            <label for="filter_date_accesses">Periodo</label>
                        </div>
                        <div class="content-container mt-1">
                            <input type="datetime-local" placeholder="Selecciona un rango de fechas" class="poa-input" id="filter_date_accesses"
                                name="filter_date_accesses" />
                        </div>
                    </div>

                    <div class="field mt-2">
                        <div class="label-container label-center">
                            <label for="filter_type">Tipo de periodo:</label>
                        </div>
                        <div class="content-container mt-1">
                            <select id="filter_type" name="filter_day" class="poa-input">
                                <option value="">Selecciona</option>
                                <option value="YYYY-MM-DD">Día</option>
                                <option value="YYYY-MM">Mes</option>
                                <option value="YYYY">Año</option>
                            </select>
                        </div>
                    </div>

                </div>
            </form>
            <h3>Número de accesos/visitas</h3>
            <div id="first_graph"></div>
        </div>

    </div>

</div>
