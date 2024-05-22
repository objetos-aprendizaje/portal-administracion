<div id="educational-resource-modal" class="modal">

    <div class="modal-body w-full md:max-w-[900px]">

        <div class="modal-header">
            <div>
                <h2 id="modal-resource-title" class="modal-title">Añade un nuevo recurso educativo</h2>
            </div>
            <div>
                <button data-modal-id="educational-resource-modal" class="modal-close-modal-btn close-modal-btn">
                    <?php e_heroicon('x-mark', 'solid'); ?>
                </button>
            </div>
        </div>


        <form id="educational-resource-form" enctype="multipart/form-data">
            @csrf

            <div class="poa-form">
                <div id="field-created-by" class="hidden">
                    <div class="field">
                        <div class="label-container label-center">
                            <label for="title">Creado por</label>
                        </div>
                        <div id="created-by"></div>
                    </div>
                </div>
                <div class="field">
                    <div class="label-container label-center">
                        <label for="title">Título <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container">
                        <input placeholder="Cronograma curso de inglés" class="poa-input" type="text" id="title"
                            name="title" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container">
                        <label for="description">Descripción</label>
                    </div>
                    <div class="content-container">
                        <textarea placeholder="El recurso contiene..." rows="5" class="poa-input" id="description" name="description"></textarea>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container">
                        <label for="image_input_file">Imagen</label>
                    </div>
                    <div class="content-container">

                        <div class="poa-input-image">
                            <img id="image_path_preview" src="{{ env('NO_IMAGE_SELECTED_PATH') }}" />

                            <span class="dimensions">*Dimensiones: Alto: 50px x Ancho: 300px. Formato: PNG, JPG. Tam.
                                Máx.: 1MB</span>

                            <div class="select-file-container">
                                <input accept="image/*" type="file" id="resource_image_input_file"
                                    name="resource_image_input_file" class="hidden" />

                                <div class="flex items-center gap-[20px]">
                                    <label for="resource_image_input_file" class="btn btn-rectangular">
                                        Subir <span class="ml-2">{{ e_heroicon('arrow-up-tray', 'outline') }}</span>
                                    </label>

                                    <span class="image-name text-[14px]" id="image-name">Ningún archivo
                                        seleccionado</span>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="resource_way">Forma de recurso <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container">
                        <select id="resource_way" name="resource_way" class="poa-select w-full">
                            <option value="" selected>Selecciona la forma del recurso</option>
                            <option value="FILE">Fichero</option>
                            <option value="URL">URL</option>
                        </select>
                    </div>
                </div>

                <div class="hidden" id="resource_file_container">
                    <div class="field hidden">
                        <div class="label-container label-center">
                            <label for="image_input_file">Recurso</label>
                        </div>
                        <div class="content-container">
                            <div class="poa-input-file  select-file-container">
                                <div class="flex-none">
                                    <input type="file" id="resource_input_file" class="hidden"
                                        name="resource_input_file">
                                    <label for="resource_input_file" class="btn btn-rectangular btn-input-file">
                                        Seleccionar archivo {{ e_heroicon('arrow-up-tray', 'outline') }}
                                    </label>
                                </div>
                                <div class="file-name text-[14px]">
                                    Ningún archivo seleccionado
                                </div>
                            </div>
                            <a id="url-resource" class="hidden text-[14px]" target="new_blank"
                                href="javascript:void(0)"></a>
                        </div>
                    </div>
                </div>

                <div class="hidden" id="url_container">
                    <div class="field">
                        <div class="label-container label-center">
                            <label for="resource_url">URL</label>
                        </div>
                        <div class="content-container">
                            <input placeholder="https://urlrecurso.com" class="poa-input" type="text"
                                id="resource_url" name="resource_url" />
                        </div>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="educational_resource_type_uid">Tipo <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container">
                        <select id="educational_resource_type_uid" name="educational_resource_type_uid"
                            class="poa-select w-full">
                            <option value="" selected>Selecciona un tipo de recurso educativo</option>
                            @foreach ($educational_resources_types as $resource_type)
                                <option value="{{ $resource_type['uid'] }}">{{ $resource_type['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="tags">Etiquetas</label>
                    </div>

                    <div class="content-container">
                        <input id="tags" name="tags" autocomplete="off" name="tags"
                            placeholder="Introduce etiquetas" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container">
                        <label for="license_type">Tipo de licencia</label>
                    </div>

                    <div class="content-container">
                        <input type="text" class="poa-input" id="license_type" name="license_type" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container">
                        <label>Metadatos</label>
                    </div>

                    <div class="content-container">
                        <div class="matadata-container" id="metadata-container"></div>

                        <div class="flex justify-end">
                            <div>
                                <button type="button" class="btn-icon" id="btn-add-metadata-pair">
                                    {{ e_heroicon('plus', 'outline') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="field mt-2">
                    <div class="label-container label-center">
                        <label for="select-categories">Categorías</label>
                    </div>

                    <div class="content-container mt-1">
                        <select id="select-categories" class="mb-4" name="categories[]" multiple
                            placeholder="Selecciona categorías..." autocomplete="off">
                            @foreach ($categories as $category)
                                <option value="{{ $category['uid'] }}">
                                    {{ $category['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>

                <input type="hidden" id="educational_resource_uid" name="educational_resource_uid"
                    value="" />

                <div class="flex justify-center mt-8 gap-4">
                    <div id="draft-button-container" class="hidden">
                        <button type="submit" id="draft-button" value="draft" class="btn btn-secondary">
                            Guardar como borrador {{ e_heroicon('check', 'outline') }}</button>
                    </div>

                    <button type="submit" value="submit" id="submit-button" class="btn btn-primary">
                        Guardar {{ e_heroicon('paper-airplane', 'outline') }}</button>
                </div>

            </div>

        </form>
    </div>

</div>

<template id="metadata-pair-template">
    <div class="flex gap-2 mb-2 metadata-pair" data-metadata-uid="">
        <div class="flex-auto">
            <input type="text" class="poa-input" name="metadata_key[]" id="" placeholder="Título" />
        </div>
        <div class="flex-auto">
            <input type="text" class="poa-input" name="metadata_value[]" id=""
                placeholder="Instrucciones de uso" />
        </div>
        <div class="flex-none">
            <button type="button" class="btn-icon btn-remove-metadata-pair">
                {{ e_heroicon('trash', 'outline') }}
            </button>
        </div>
    </div>
</template>
