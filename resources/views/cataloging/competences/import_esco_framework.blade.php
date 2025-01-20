<div id="import-esco-framework-modal" class="modal">

    <div class="modal-body w-full md:max-w-[900px]">

        <div class="modal-header">
            <div>
                <h2 class="modal-title">Importar marco ESCO</h2>
            </div>

            <div>
                <button data-modal-id="import-esco-framework-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php eHeroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>

        <p>
            Para importar el marco ESCO, descárgalo desde aquí <a
                href="https://esco.ec.europa.eu/en/use-esco/download">https://esco.ec.europa.eu/en/use-esco/download</a>
        </p>
        <p>
            Posteriormente, selecciona la versión que desees, en Content selecciona "classification" y por último
            selecciona el lenguaje.
        </p>

        <p>
            Tras la descarga, descomprime el archivo y adjunta los ficheros <span
                class="font-roboto-bold">skillsHierarchy.csv</span>, <span class="font-roboto-bold">skills.csv</span> y
            <span class="font-roboto-bold">broaderRelationsSkillPillar.csv</span>
        </p>

        <p>Por favor, ten en cuenta que este proceso es muy tedioso debido a la longitud del marco ESCO, por lo que es muy probable que tarde varios minutos</p>

        <div class="poa-form mt-4">

            <form id="esco-framework-form">

                <div class="field">
                    <div class="label-container label-center">
                        <label for="truetype_regular_file_path">skillsHierarchy.csv</label>
                    </div>

                    <div class="content-container">
                        <div class="poa-input-file select-file-container">
                            <div class="flex-none">
                                <input type="file" id="skills_hierarchy_file" data-font="skills_hierarchy_file"
                                    class="hidden input-font" name="skills_hierarchy_file" accept=".csv">
                                <label for="skills_hierarchy_file" class="btn btn-rectangular btn-input-file">
                                    Seleccionar archivo {{ eHeroicon('arrow-up-tray', 'outline') }}
                                </label>
                            </div>
                            <div class="file-name skills_hierarchy_file link-label">
                                Ningún archivo seleccionado
                            </div>
                        </div>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="truetype_regular_file_path">skills.csv</label>
                    </div>

                    <div class="content-container">
                        <div class="poa-input-file select-file-container">
                            <div class="flex-none">
                                <input type="file" id="skills_file" data-font="skills_file" class="hidden input-font"
                                    name="skills_file" accept=".csv">
                                <label for="skills_file" class="btn btn-rectangular btn-input-file">
                                    Seleccionar archivo {{ eHeroicon('arrow-up-tray', 'outline') }}
                                </label>
                            </div>
                            <div class="file-name skills_file link-label">
                                Ningún archivo seleccionado
                            </div>
                        </div>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="truetype_regular_file_path">broaderRelationsSkillPillar.csv</label>
                    </div>

                    <div class="content-container">
                        <div class="poa-input-file select-file-container">
                            <div class="flex-none">
                                <input type="file" id="broader_relations_skill_pillar_file"
                                    data-font="broader_relations_skill_pillar_file" class="hidden input-font"
                                    name="broader_relations_skill_pillar_file" accept=".csv">
                                <label for="broader_relations_skill_pillar_file"
                                    class="btn btn-rectangular btn-input-file">
                                    Seleccionar archivo {{ eHeroicon('arrow-up-tray', 'outline') }}
                                </label>
                            </div>
                            <div class="file-name broader_relations_skill_pillar_file link-label">
                                Ningún archivo seleccionado
                            </div>
                        </div>
                    </div>
                </div>

                <div class="btn-block">
                    <button type="submit" class="btn btn-primary">Importar
                        {{ eHeroicon('folder-plus', 'outline') }}</button>

                    <button data-modal-id="import-esco-framework-modal" type="button"
                        class="btn btn-secondary close-modal-btn">
                        Cancelar {{ eHeroicon('x-mark', 'outline') }}</button>
                </div>

            </form>

        </div>

    </div>

</div>
