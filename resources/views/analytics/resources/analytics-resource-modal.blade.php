<div id="analytics-resource-modal" class="modal">

    <div class="modal-body w-full md:max-w-[1000px]">

        <div class="modal-header">
            <div>
                <h2 id="analytics-resource-modal-title" class="modal-title"></h2>
            </div>
            <div>
                <button data-modal-id="analytics-resource-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php eHeroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>

        <div>
            <form id="course-form" enctype="multipart/form-data" class="mb-12">
                @csrf

                <div class="poa-form">

                    <div class="field mt-2">
                        <div class="label-container label-center">
                            <label for="last_access_resource">Último acceso al recurso:</label>
                        </div>
                        <div class="content-container mt-1">
                            <div id="last_access_resource"></div>
                        </div>
                    </div>
                    <div class="field mt-2">
                        <div class="label-container label-center">
                            <label for="last_visit_resource">Última visita al recurso: </label>
                        </div>
                        <div class="content-container mt-1">
                            <div id="last_visit_resource"></div>
                        </div>
                    </div>
                    <div class="field mt-2">
                        <div class="label-container label-center">
                            <label for="unique_users_resource">Total de visitas y accesos:</label>
                        </div>
                        <div class="content-container mt-1">
                            <div id="unique_users_resource"></div>
                        </div>
                    </div>

                    <div class="field mt-2">
                        <div class="label-container label-center">
                            <label for="filter_date_accesses_resource">Periodo</label>
                        </div>
                        <div class="content-container mt-1">
                            <input type="datetime-local" placeholder="Selecciona un rango de fechas" class="poa-input" id="filter_date_accesses_resource"
                                name="filter_date_accesses_resource" />
                        </div>
                    </div>

                    <div class="field mt-2">
                        <div class="label-container label-center">
                            <label for="filter_type_resource">Tipo de periodo:</label>
                        </div>
                        <div class="content-container mt-1">
                            <select id="filter_type_resource" name="filter_day_resource" class="poa-input">
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
            <div id="second_graph"></div>
        </div>

    </div>

</div>
