<div id="enroll-educational-program-csv-modal" class="modal">
    <div class="modal-body w-full md:max-w-[800px]">
        <div class="modal-header">
            <div>
                <h2 id="enroll-educational-program-modal-title">Asignar alumnos al curso mediante CSV</h2>
            </div>

            <div>
                <button data-modal-id="enroll-educational-program-csv-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>


        <div class="poa-form">

            <div class="grid grid-cols-1  gap-4">

                </div class="flex">
                    <span>Descargue un CSV de ejemplo mediante este enlace:</span> <a href="/csv/data.csv" target="_blank">DESCARGAR</a>
                </div>

                <div class="content-container w-full">

                    <p class="mb-2">Suba el archivo con los datos:</p>
                    <div class="poa-input-file">
                        <div class="flex-none">
                            <input type="file" id="attachment" class="hidden" name="attachment">
                            <label for="attachment" class="btn btn-rectangular btn-input-file">
                                Seleccionar archivo {{ e_heroicon('arrow-up-tray', 'outline') }}
                            </label>
                        </div>
                        <div id="file-name" class="file-name text-[14px]">
                            Ningún archivo seleccionado
                        </div>
                    </div>

                    <a id="attachment-download" class=" text-[14px]" target="new_blank"
                        href="javascript:void(0)"></a>

                </div>

                <div class="flex justify-center mt-8">
                    <button id="enroll-educational-program-csv-btn" type="button" class="btn btn-primary">
                        Subir {{ e_heroicon('arrow-up-tray', 'outline') }}</button>
                </div>
            </div>



        </div>

    </div>

</div>
