<div id="filter-educational-resources-modal" class="modal">
    <div class="modal-body w-full md:max-w-[800px]">
        <div class="modal-header">
            <div>
                <h2 id="filter-educational-resources-modal-title">Filtrar recursos educativos</h2>
            </div>

            <div>
                <button data-modal-id="filter-educational-resources-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php eHeroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>


        <div class="poa-form">

            <div class="grid md:grid-cols-2 grid-cols-1  gap-4">
                <div>
                    <div class="label-container label-center">
                        <label for="filter_resource_way">Forma de recurso</span></label>
                    </div>
                    <div class="content-container">
                        <select id="filter_resource_way" name="filter_resource_way" class="poa-select w-full">
                            <option value="" selected>Selecciona la forma del recurso</option>
                            <option value="FILE">Fichero</option>
                            <option value="URL">URL</option>
                            <option value="IMAGEN">Imagen</option>
                            <option value="PDF">PDF</option>
                            <option value="VIDEO">Vídeo</option>
                            <option value="AUDIO">Audio</option>
                        </select>
                    </div>
                </div>

                <div>
                    <div class="label-container label-center">
                        <label for="filter_educational_resource_type_uid">Tipo</span></label>
                    </div>
                    <div class="content-container">
                        <select id="filter_educational_resource_type_uid" name="filter_educational_resource_type_uid"
                            class="poa-select w-full">
                            <option value="" selected>Selecciona un tipo de recurso educativo</option>
                            @foreach ($educational_resources_types as $resource_type)
                                <option value="{{ $resource_type['uid'] }}">{{ $resource_type['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <div class="label-container label-center">
                        <label for="filter_select_categories">Categorías</label>
                    </div>

                    <div class="content-container mt-1">
                        <select id="filter_select_categories" class="mb-4" name="categories[]" multiple
                            placeholder="Selecciona categorías..." autocomplete="off">
                            @foreach ($categories as $category)
                                <option value="{{ $category['uid'] }}">
                                    {{ $category['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>

                <div>
                    <div class="label-container label-center">
                        <label for="filter_embeddings">Embeddings</label>
                    </div>

                    <div class="content-container mt-1">
                        <select id="filter_embeddings" name="filter_embeddings" class="poa-select w-full">
                            <option value="" selected></option>
                            <option value="1">Sí</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex justify-center mt-8">
                <button id="filter-btn" type="button" class="btn btn-primary">
                    Filtrar {{ eHeroicon('adjustments-horizontal', 'outline') }}</button>
            </div>

        </div>

    </div>

</div>
