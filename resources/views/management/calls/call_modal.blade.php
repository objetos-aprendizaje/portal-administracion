<div id="call-modal" class="modal">

    <div class="modal-body w-full md:max-w-[950px]">

        <div class="modal-header">
            <div>
                <h2 class="modal-title">Título convocatoria</h2>
            </div>

            <div>
                <button data-modal-id="call-modal" class="modal-close-modal-btn close-modal-btn">

                    <?php eHeroicon('x-mark', 'solid'); ?>
                </button>
            </div>

        </div>

        <form id="call-form">
            @csrf

            <div class="poa-form">
                <div class="field">
                    <div class="label-container label-center">
                        <label for="name">Nombre <span class="text-red-500">*</span></label>
                    </div>
                    <div class="content-container">
                        <input maxlength="255" class="required poa-input" placeholder="Convocatoria 2023" type="text"
                            id="name" name="name" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container">
                        <label for="description">Descripción</label>
                    </div>
                    <div class="content-container">
                        <textarea maxlength="1000" class="w-full poa-input" placeholder="La convocatoria de este año tratará de..."
                            rows="5" id="description" name="description"></textarea>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="attachment">Adjunto</label>
                    </div>

                    <div class="content-container w-full">

                        <div class="poa-input-file">
                            <div class="flex-none">
                                <input type="file" id="attachment" class="hidden" name="attachment">
                                <label for="attachment" class="btn btn-rectangular btn-input-file">
                                    Seleccionar archivo {{ eHeroicon('arrow-up-tray', 'outline') }}
                                </label>
                            </div>
                            <div id="file-name" class="file-name text-[14px]">
                                Ningún archivo seleccionado
                            </div>
                        </div>

                        <a id="attachment-download" class=" text-[14px]" target="new_blank" href="javascript:void(0)">
                            Descargar adjunto
                        </a>

                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="start-date">Fecha inicio <span class="text-red-500">*</span></label>
                    </div>
                    <div class="content-container">
                        <input class="required poa-input" placeholder="Gran Vía, 1" type="datetime-local"
                            id="start_date" name="start_date" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="end-date">Fecha fin <span class="text-red-500">*</span></label>
                    </div>
                    <div class="content-container">
                        <input class="required poa-input" placeholder="Gran Vía, 1" type="datetime-local" id="end_date"
                            name="end_date" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="program_types">Tipos de programa <span class="text-red-500">*</span></label>
                    </div>

                    <div class="content-container">
                        <select id="program_types" name="program_types[]" multiple
                            placeholder="Selecciona un tipo de programa..." autocomplete="off">

                            @foreach ($educational_program_types as $program_type)
                                <option value="{{ $program_type['uid'] }}">{{ $program_type['name'] }}</option>
                            @endforeach

                        </select>
                    </div>

                </div>

                <div class="btn-block">
                    <button type="submit" class="btn btn-primary">
                        Guardar {{ eHeroicon('paper-airplane', 'outline') }}</button>

                    <button data-modal-id="call-modal" type="button" class="btn btn-secondary close-modal-btn">Cancelar
                        {{ eHeroicon('x-mark', 'outline') }}</button>
                </div>
            </div>

            <input type="hidden" id="call_uid" name="call_uid" value="" />

        </form>

    </div>

</div>
