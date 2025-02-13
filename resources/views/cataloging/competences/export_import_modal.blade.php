<div id="export_import-modal" class="modal">

    <div class="modal-body w-full md:max-w-[900px]">

        <div class="modal-header">
            <div>
                <h2 id="modal-resource-title" class="modal-title">Exportar/Importar</h2>
            </div>
            <div>
                <button data-modal-id="export_import-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php eHeroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>

        <div class="flex gap-[30px] mb-[30px]">
            <div class="w-full text-center">
                <p class="mb-[40px]">Exportar marcos de competencias</p>
                <button id="btn-export" type="button" class="btn btn-primary" id="btn-export">Exportar
                    {{ eHeroicon('arrow-down-tray', 'outline') }}</button>
            </div>

            <div class="border-l my-3"></div>

            <div class=" w-full text-center">
                <form id="import-framework-form">

                    <p class="mb-[8px]">Importar marcos de competencias</p>

                    <div class="content-container w-full mb-[6px]">
                        <div class="poa-input-file">
                            <div class="flex-none">
                                <input type="file" id="data-json-import" class="hidden" name="data-json-import"
                                    accept=".json">
                                <label for="data-json-import" class="btn btn-rectangular btn-input-file">
                                    Seleccionar archivo {{ eHeroicon('arrow-up-tray', 'outline') }}
                                </label>
                            </div>
                            <div id="file-name" class="file-name text-[14px]">
                                Ningún archivo seleccionado
                            </div>
                        </div>

                        <a id="attachment-download" class="text-[14px]" target="_blank" href="javascript:void(0)">
                            Descargar adjunto
                        </a>

                    </div>

                    <p class="mb-[26px]">
                        <a class="underline" download href="/json/data.json">Descargar plantilla guía</a>
                    </p>

                    <button type="submit" type="submit" class="btn btn-primary">Importar
                        {{ eHeroicon('arrow-up-tray', 'outline') }}</button>
                </form>
            </div>
        </div>

        <div class="btn-block">
            <button data-modal-id="export_import-modal" type="button" class="btn btn-secondary close-modal-btn">
                Cerrar {{ eHeroicon('x-mark', 'outline') }}</button>
        </div>

    </div>

</div>
