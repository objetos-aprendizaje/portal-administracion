<div id="analytics-user-modal" class="modal">

    <div class="modal-body w-full md:max-w-[1000px]">

        <div class="modal-header">
            <div>
                <h2 id="analytics-user-modal-title" class="modal-title"></h2>
            </div>

            <div>
                <button data-modal-id="analytics-user-modal" class="modal-close-modal-btn close-modal-btn">
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
                            <label for="filter_date_accesses">Periodo</label>
                        </div>
                        <div class="content-container mt-1">
                            <input type="datetime-local" placeholder="Selecciona un rango de fechas" class="poa-input"
                                id="filter_date_accesses" name="filter_date_accesses" />
                        </div>
                    </div>

                    <div class="field mt-2">
                        <div class="label-container label-center">
                            <label for="filter_type">Intervalo de tiempo:</label>
                        </div>
                        <div class="content-container mt-1">
                            <select id="filter_type" name="filter_day" class="poa-input">
                                <option value="YYYY-MM-DD">Día</option>
                                <option value="YYYY-MM">Mes</option>
                                <option value="YYYY">Año</option>
                            </select>
                        </div>
                    </div>

                    <div class="field mt-2">
                        <div class="label-container label-center">
                            <label for="last-login-date">Último acceso:</label>
                        </div>

                        <div class="content-container mt-1">
                            <div id="last-login-date"></div>
                        </div>
                    </div>

                    <div class="field mt-2">
                        <div class="label-container label-center">
                            <label for="count-inscribed-courses">Nº de cursos inscritos:</label>
                        </div>

                        <div class="content-container mt-1">
                            <div id="count-inscribed-courses"></div>
                        </div>
                    </div>
                </div>
            </form>

            <h3>Visitas públicas a cursos no matriculados</h3>
            <p>
                Se registra cada consulta realizada por el usuario a los cursos desde su ficha de información en el
                Portal Web.
            </p>
            <div id="first_graph" class="mb-12"></div>

            <h3>Accesos a cursos a matriculados</h3>
            <p>
                Se contabiliza un acceso cada vez que el usuario, desde su perfil, ingresa a uno de los cursos en los
                que está matriculado y navega al sistema de gestión de aprendizaje (LMS).
            </p>
            <div id="second_graph" class="mb-12"></div>

            <h3>Accesos a recursos educativos</h3>
            <p>
                Se registra cada consulta realizada por el usuario a los recursos educativos desde su ficha de
                información en el Portal Web.
            </p>
            <div id="third_graph" class="mb-12"></div>

        </div>

    </div>

</div>
