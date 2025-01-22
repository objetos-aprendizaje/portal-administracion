import {
    showModal,
    hideModal,
    showModalConfirmation,
} from "../modal_handler.js";
import {
    controlsPagination,
    updateArrayRecords,
    tabulatorBaseConfig,
    updatePaginationInfo,
    controlsSearch,
} from "../tabulator_handler.js";
import {
    getCsrfToken,
    updateInputImage,
    showFormErrors,
    resetFormErrors,
    updateInputFile,
    getMultipleTomSelectInstance,
    getCreateElementsTomSelectInstance,
    toggleFormFields,
    apiFetch,
    getOptionsSelectedTomSelectInstance,
    getMultipleFreeEmailsTomSelectInstance,
} from "../app.js";
import { heroicon } from "../heroicons.js";
import { TabulatorFull as Tabulator } from "tabulator-tables";
import { showToast } from "../toast.js";
import InfiniteTree from "infinite-tree";
import renderer from "../renderer_infinite_tree.js";

let resourcesTable;
let selectedResources = [];
let filters = [];
const endPointTable = "/learning_objects/educational_resources/get_resources";
let tomSelectTags;
let tomSelectContactEmails;
let tomSelectCategories;
let tomFilterSelectCategories;
let metadataCounter = 0;
let treeCompetencesLearningResults = null;
let selectedLearningResults = new Set();

document.addEventListener("DOMContentLoaded", () => {
    initHandlers();

    initializeResourcesTable();

    initializeTomSelect();
    updateInputImage();
    updateInputFile();
    controlShowHideResourcesWay();
});

function initHandlers() {
    document
        .getElementById("btn-reload-table")
        .addEventListener("click", function () {
            resourcesTable.setData(endPointTable);
        });

    document
        .getElementById("btn-add-resource")
        .addEventListener("click", () => {
            newEducationalResource();
        });

    document
        .getElementById("btn-delete-resources")
        .addEventListener("click", () => {
            if (selectedResources.length) {
                showModalConfirmation(
                    "Eliminar recursos educativos",
                    "¿Está seguro que desea eliminar los recursos educativos seleccionados?",
                    "delete_educational_resources"
                ).then((resultado) => {
                    if (resultado) {
                        deleteResources();
                    }
                });
            } else {
                showToast(
                    "Debe seleccionar al menos un recurso educativo para eliminarlo",
                    "error"
                );
            }
        });

    document
        .getElementById("confirm-change-statuses-btn")
        .addEventListener("click", submitChangeStatusesResources);

    const changeStatusesBtn = document.getElementById("change-statuses-btn");

    if (changeStatusesBtn) {
        changeStatusesBtn.addEventListener("click", changeStatusesResources);
    }

    document
        .getElementById("educational-resource-form")
        .addEventListener("submit", submitEducationalResource);

    document
        .getElementById("btn-add-metadata-pair")
        .addEventListener("click", function () {
            addMetadataPair();
        });

    document
        .querySelector(".matadata-container")
        .addEventListener("click", removeMetadataPair);

    document
        .getElementById("filter-educational-resources-btn")
        .addEventListener("click", function () {
            showModal("filter-educational-resources-modal");
        });
    document
        .getElementById("filter-btn")
        .addEventListener("click", function () {
            controlSaveHandlerFilters();
        });

    document
        .getElementById("delete-all-filters")
        .addEventListener("click", function () {
            resetFilters();
        });

    const regenerateEmbeddingsBtn = document.getElementById(
        "btn-regenerate-embeddings"
    );

    if (regenerateEmbeddingsBtn) {
        regenerateEmbeddingsBtn.addEventListener("click", function () {
            if (!selectedResources.length) {
                showToast("No has seleccionado ningún recurso", "error");
                return;
            }

            showModalConfirmation(
                "Regenerar embeddings",
                "¿Estás seguro de que quieres regenerar los embeddings de los recursos educativos seleccionados?"
            ).then((result) => {
                if (!result) return;
                regenerateEmbeddingsEducationalResources();
            });
        });
    }

    const generateTagsBtn = document.getElementById("generate-tags-btn");
    if (generateTagsBtn) {
        generateTagsBtn.addEventListener("click", () => {
            generateTags();
            generateTagsBtn.classList.add("hidden");
            setTimeout(() => {
                generateTagsBtn.classList.remove("hidden");
            }, 10000);
        });
    }

    const generateMetadataBtn = document.getElementById(
        "generate-metadata-btn"
    );

    if (generateMetadataBtn) {
        generateMetadataBtn.addEventListener("click", function () {
            generateMetadata();
        });
    }
}

function addMetadataPair(data = null) {
    // Clona el template
    const template = document
        .getElementById("metadata-pair-template")
        .content.cloneNode(true);
    const metadataPair = template.querySelector(".metadata-pair");

    // Asigna los IDs y nombres correctos a los inputs
    const keyInput = metadataPair.querySelector('[name="metadata_key[]"]');
    keyInput.id = `metadata.${metadataCounter}.metadata_key`;

    const valueInput = metadataPair.querySelector('[name="metadata_value[]"]');
    valueInput.id = `metadata.${metadataCounter}.metadata_value`;

    if (data) {
        keyInput.value = data.key;
        valueInput.value = data.value;
    }
    // Añade el par de metadatos al DOM
    document.querySelector(".matadata-container").appendChild(metadataPair);

    // Incrementa el contador
    metadataCounter++;
}

function removeMetadataPair(event) {
    let target = event.target;

    if (!target.classList.contains(".btn-remove-metadata-pair")) {
        target = target.closest(".btn-remove-metadata-pair");
    }

    if (target) {
        target.closest(".metadata-pair").remove();
    }
}

function getMetadataArray() {
    const metadata = [];

    document.querySelectorAll(".metadata-pair").forEach((pair) => {
        const uid = pair.getAttribute("data-metadata-uid");
        const key = pair.querySelector('[name="metadata_key[]"]').value;
        const value = pair.querySelector('[name="metadata_value[]"]').value;

        metadata.push({
            uid: uid || null,
            metadata_key: key,
            metadata_value: value,
        });
    });

    return metadata;
}

function loadMetadata(metadataArray) {
    const metadataContainer = document.getElementById("metadata-container");

    metadataArray.forEach((metadata, index) => {
        const metadataElement = createMetadataElementMetadata(metadata, index);
        metadataContainer.appendChild(metadataElement);
        metadataCounter++;
    });
}

function createMetadataElementMetadata(metadata, id) {
    // Clona la plantilla y obtén los elementos de entrada
    const template = document
        .getElementById("metadata-pair-template")
        .content.cloneNode(true);
    const keyInput = template.querySelector('[name="metadata_key[]"]');
    const valueInput = template.querySelector('[name="metadata_value[]"]');

    // Configura los valores de los campos de entrada
    keyInput.value = metadata.metadata_key;
    keyInput.id = `metadata.${id}.metadata_key`;
    valueInput.value = metadata.metadata_value;
    valueInput.id = `metadata.${id}.metadata_value`;

    // Agrega un atributo data-uid al elemento del par de metadatos
    template
        .querySelector(".metadata-pair")
        .setAttribute("data-metadata-uid", metadata.uid);

    return template;
}

function initializeTomSelect() {
    tomSelectTags = getCreateElementsTomSelectInstance("#tags");
    tomSelectCategories = getMultipleTomSelectInstance("#select-categories");
    tomFilterSelectCategories = getMultipleTomSelectInstance(
        "#filter_select_categories"
    );
    tomSelectContactEmails =
        getMultipleFreeEmailsTomSelectInstance("#contact_emails");
}

function controlShowHideResourcesWay() {
    const resourceWaySelect = document.getElementById("resource_way");

    resourceWaySelect.addEventListener("change", function () {
        showHideResourcesWay(this.value);
    });
}

/**
 * Muestra y oculta los campos de los recursos en función del tipo
 */
function showHideResourcesWay(value) {
    const resourceFileContainer = document.getElementById(
        "resource_file_container"
    );
    const urlContainer = document.getElementById("url_container");
    const acceptInput = document.getElementById("resource_input_file");

    if (value === "FILE") {
        resourceFileContainer.classList.remove("hidden");
        urlContainer.classList.add("hidden");
        acceptInput.accept = "";
    } else if (value === "IMAGE") {
        resourceFileContainer.classList.remove("hidden");
        urlContainer.classList.add("hidden");
        acceptInput.accept = ".jpg, .jpeg, .png";
    } else if (value === "PDF") {
        resourceFileContainer.classList.remove("hidden");
        urlContainer.classList.add("hidden");
        acceptInput.accept = ".pdf";
    } else if (value === "VIDEO") {
        resourceFileContainer.classList.remove("hidden");
        urlContainer.classList.add("hidden");
        acceptInput.accept = ".mp4, .mkv";
    } else if (value === "AUDIO") {
        resourceFileContainer.classList.remove("hidden");
        urlContainer.classList.add("hidden");
        acceptInput.accept = ".mp3";
    } else if (value === "URL") {
        resourceFileContainer.classList.add("hidden");
        urlContainer.classList.remove("hidden");
        acceptInput.accept = "";
    } else {
        urlContainer.classList.add("hidden");
        resourceFileContainer.classList.add("hidden");
    }
}

/**
 * Muestra el modal para añadir un nuevo recurso educativo.
 */
function newEducationalResource() {
    const draftButtonContainer = document.getElementById(
        "draft-button-container"
    );
    draftButtonContainer.classList.remove("hidden");

    resetModal();

    toggleResourcesFields("educational-resource-form", false);
    showModal("educational-resource-modal", "Nuevo recurso educativo");
}

/**
 * Maneja el evento de clic en el botón para aplicar los filtros.
 * Recoge los filtros del modal, los muestra en la interfaz y vuelve a inicializar
 * la tabla de cursos con los nuevos filtros aplicados.
 */
function controlSaveHandlerFilters() {
    filters = collectFilters();

    showFilters();

    hideModal("filter-educational-resources-modal");

    initializeResourcesTable();
}

/**
 * Recoge todos los filtros aplicados en el modal de filtros.
 * Obtiene los valores de los elementos de entrada y los añade al array
 * de filtros seleccionados.
 */
function collectFilters() {
    let selectedFilters = [];
    /**
     *
     * @param {*} name nombre del filtro
     * @param {*} value Valor correspondiente a la opción seleccionada
     * @param {*} option Opción seleccionada
     * @param {*} filterKey Id correspondiente al filtro y al campo input al que corresponde
     * @param {*} database_field Nombre del campo de la BD correspondiente al filtro
     * @param {*} filterType Tipo de filtro
     *
     * Añade filtros al array
     */
    function addFilter(name, value, option, filterKey, database_field = "") {
        if (value && value !== "") {
            selectedFilters.push({
                name,
                value,
                option,
                filterKey,
                database_field,
            });
        }
    }
    let filterResourceWay = document.getElementById("filter_resource_way");

    if (filterResourceWay.value) {
        addFilter(
            "Forma de recurso",
            filterResourceWay.value,
            filterResourceWay.value == "FILE" ? "Fichero" : "URL",
            "filter_resource_way",
            "resource_way"
        );
    }

    let filterEducationalResourceTypeUid = document.getElementById(
        "filter_educational_resource_type_uid"
    );
    if (filterEducationalResourceTypeUid.value) {
        let selectedFilterOptionText =
            filterEducationalResourceTypeUid.options[
                filterEducationalResourceTypeUid.selectedIndex
            ].text;

        addFilter(
            "Tipo",
            filterEducationalResourceTypeUid.value,
            selectedFilterOptionText,
            "filter_educational_resource_type_uid",
            "educational_resource_type_uid"
        );
    }

    let selectElementFilterEmbeddings =
        document.getElementById("filter_embeddings");
    if (selectElementFilterEmbeddings.value) {
        addFilter(
            "¿Tiene embeddings?",
            selectElementFilterEmbeddings.value,
            selectElementFilterEmbeddings.value == "1" ? "Sí" : "No",
            "filter_embeddings",
            "embeddings"
        );
    }

    if (tomFilterSelectCategories) {
        const categoriesFilter = tomFilterSelectCategories.getValue();

        const selectedFilterCategoriesLabel =
            getOptionsSelectedTomSelectInstance(tomFilterSelectCategories);

        if (categoriesFilter.length)
            addFilter(
                "Categorías",
                categoriesFilter,
                selectedFilterCategoriesLabel,
                "Categories",
                "categories"
            );
    }

    return selectedFilters;
}

/**
 * Muestra los filtros aplicados en la interfaz de usuario.
 * Recorre el array de 'filters' y genera el HTML para cada filtro,
 * permitiendo su visualización y posterior eliminación. Además muestra u oculta
 * el botón de eliminación de filtros
 */
function showFilters() {
    // Eliminamos todos los filtros
    const currentFilters = document.querySelectorAll(".filter");

    // Recorre cada elemento y lo elimina
    currentFilters.forEach(function (filter) {
        filter.remove();
    });

    filters.forEach((filter) => {
        // Crea un nuevo div
        const newDiv = document.createElement("div");

        // Agrega la clase 'filter' al div
        newDiv.classList.add("filter");

        // Establece el HTML del nuevo div
        newDiv.innerHTML = `
            <div>${filter.name}: ${filter.option}</div>
            <button data-filter-key="${
                filter.filterKey
            }" class="delete-filter-btn">${heroicon(
            "x-mark",
            "outline"
        )}</button>
        `;

        // Agrega el nuevo div al div existente
        document.getElementById("filters").prepend(newDiv);
    });

    const deleteAllFiltersBtn = document.getElementById("delete-all-filters");

    if (filters.length == 0) deleteAllFiltersBtn.classList.add("hidden");
    else deleteAllFiltersBtn.classList.remove("hidden");

    // Agregamos los listeners de eliminación a los filtros
    document.querySelectorAll(".delete-filter-btn").forEach((deleteFilter) => {
        deleteFilter.addEventListener("click", (event) => {
            controlDeleteFilters(event.currentTarget);
        });
    });
}

function resetFilters() {
    filters = [];
    showFilters();
    initializeResourcesTable();

    tomFilterSelectCategories.clear();
    document.getElementById("filter_resource_way").value = "";
    document.getElementById("filter_educational_resource_type_uid").value = "";
    document.getElementById("filter_embeddings").value = "";
}

/**
 * Maneja el evento de clic para eliminar un filtro específico.
 * Cuando se hace clic en un botón con la clase 'delete-filter-btn',
 * este elimina el filtro correspondiente del array 'filters' y actualiza
 * la visualización y la tabla de cursos.
 */
function controlDeleteFilters(deleteBtn) {
    const filterKey = deleteBtn.getAttribute("data-filter-key");

    filters = filters.filter((filter) => filter.filterKey !== filterKey);

    if (filterKey == "Categories") tomFilterSelectCategories.clear();
    else document.getElementById(filterKey).value = "";

    showFilters();
    initializeResourcesTable();
}

/**
 * Inicializa la tabla de recursos con sus columnas y configuraciones.
 */
function initializeResourcesTable() {
    const columns = [
        {
            title: '<div class="checkbox-cell"><input type="checkbox" id="select-all-checkbox"/></div>',
            headerClick: function (e) {
                const selectAllCheckbox = e.target;
                if (selectAllCheckbox.type === "checkbox") {
                    // Asegúrate de que el clic fue en el checkbox
                    resourcesTable.getRows().forEach((row) => {
                        const cell = row.getCell("select");
                        const checkbox = cell
                            .getElement()
                            .querySelector('input[type="checkbox"]');
                        checkbox.checked = selectAllCheckbox.checked;
                        selectedResources = updateArrayRecords(
                            checkbox,
                            row.getData(),
                            selectedResources
                        );
                    });
                }
            },
            field: "select",
            formatter: function (cell, formatterParams, onRendered) {
                const uid = cell.getRow().getData().uid;
                return `<input type="checkbox" data-uid="${uid}"/>`;
            },
            cssClass: "checkbox-cell",
            cellClick: function (e, cell) {
                // Lógica cuando se hace clic en la celda
                const checkbox = e.target;
                const data = cell.getRow().getData();

                selectedResources = updateArrayRecords(
                    checkbox,
                    data,
                    selectedResources
                );
            },
            headerSort: false,
            width: 60,
        },
        { title: "Identificador", field: "identifier", widthGrow: 1 },
        {
            title: "Estado",
            field: "status.name",
            widthGrow: 2,
            formatter: function (cell, formatterParams, onRendered) {
                let color = getStatusEducationalResourceColor(
                    cell.getRow().getData().status.code
                );

                return `
                <div class="label-status" style="background-color: ${color};">${
                    cell.getRow().getData().status.name
                }</div>
                `;
            },
        },
        { title: "Título", field: "title", widthGrow: 4 },
        { title: "Descripción", field: "description", widthGrow: 2 },
        { title: "Tipo", field: "type.name", widthGrow: 2 },
        {
            title: "¿Tiene embeddings?",
            field: "embeddings_status",
            formatter: function (cell, formatterParams, onRendered) {
                const data = cell.getValue();
                if (data == 0) return "No";
                else return "Si";
            },
            widthGrow: 2,
            visible: true,
        },
        {
            title: "",
            field: "actions",
            formatter: function (cell, formatterParams, onRendered) {
                return `<button type="button" class='btn action-btn' title='Editar'>${heroicon(
                    "pencil-square",
                    "outline"
                )}</button>`;
            },
            cellClick: function (e, cell) {
                e.preventDefault();

                const resourceClicked = cell.getRow().getData();
                loadResourceModal(resourceClicked.uid);
            },
            cssClass: "text-center",
            widthGrow: 1,
            headerSort: false,
            width: 60,
        },
    ];

    resourcesTable = new Tabulator("#resources-table", {
        ajaxURL: endPointTable,
        ...tabulatorBaseConfig,
        columns: columns,
        ajaxParams: {
            filters: {
                ...filters,
            },
        },
        ajaxResponse: function (url, params, response) {
            updatePaginationInfo(resourcesTable, response, "resources-table");

            selectedResources = [];

            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
    });

    controlsPagination(resourcesTable, "resources-table");
    controlsSearch(resourcesTable, endPointTable, "resources-table");
}

function regenerateEmbeddingsEducationalResources() {
    const educationalResourcesSelected = selectedResources.map(
        (resource) => resource.uid
    );
    const params = {
        url: `/learning_objects/educational_resources/regenerate_embeddings`,
        method: "POST",
        stringify: true,
        body: {
            educational_resources_uids: educationalResourcesSelected,
        },
        toast: true,
        loader: true,
    };

    apiFetch(params).then(() => {
        resourcesTable.replaceData(endPointTable);
    });
}

/**
 * Carga la información del recurso seleccionado en el modal.
 */
async function loadResourceModal(uid) {
    const params = {
        url: `/learning_objects/educational_resources/get_resource/${uid}`,
        method: "GET",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": getCsrfToken(),
        },
        loader: true,
    };

    apiFetch(params).then((data) => {
        resetModal();
        fillFormResourceModal(data);
        showModal("educational-resource-modal", "Editar recurso educativo");
    });
}

/**
 * Rellena el formulario del modal con la información del recurso.
 */
function fillFormResourceModal(resource) {
    const draftButtonContainer = document.getElementById(
        "draft-button-container"
    );

    if (resource.status.code == "INTRODUCTION")
        draftButtonContainer.classList.remove("hidden");
    else draftButtonContainer.classList.add("hidden");

    if (resource.resource_path) {
        const urlResourceElement = document.getElementById("url-resource");
        urlResourceElement.classList.remove("hidden");
        document.getElementById("url-resource").href =
            "/" + resource.resource_path;
        const resourceName = resource.resource_path.split("/").pop();
        urlResourceElement.innerText = resourceName;
    }

    document.getElementById("field-created-by").classList.remove("hidden");
    const createdByDiv = document.getElementById("created-by");

    if (resource.creator_user) {
        createdByDiv.innerText =
            resource.creator_user.first_name +
            " " +
            resource.creator_user.last_name;
    } else {
        createdByDiv.innerText = "No disponible";
    }

    document.getElementById("field-has-embeddings").classList.remove("hidden");
    document.getElementById("has-embeddings").innerText = resource.embeddings
        ? "Sí"
        : "No";

    document.getElementById("educational_resource_uid").value = resource.uid;
    document.getElementById("title").value = resource.title;
    document.getElementById("description").value = resource.description;
    document.getElementById("educational_resource_type_uid").value =
        resource.educational_resource_type_uid;
    document.getElementById("license_type_uid").value =
        resource.license_type_uid;
    document.getElementById("resource_way").value = resource.resource_way;
    document.getElementById("resource_url").value = resource.resource_url;

    showHideResourcesWay(resource.resource_way);

    loadMetadata(resource.metadata);
    if (resource.tags) {
        resource.tags.forEach((tag) => {
            tomSelectTags.addOption({ value: tag.tag, text: tag.tag });
            tomSelectTags.addItem(tag.tag);
        });
    }

    if (resource.categories) {
        resource.categories.forEach((category) => {
            tomSelectCategories.addOption({
                value: category.uid,
                text: category.name,
            });
            tomSelectCategories.addItem(category.uid);
        });
    }

    if (resource.learning_results) {
        selectLearningResultsUser(resource.learning_results);
    }

    // Mostramos u ocultamos el link de la imagen
    if (resource.image_path) {
        document.getElementById("image_path_preview").src =
            "/" + resource.image_path;
    } else {
        document.getElementById("image_path_preview").src = defaultImagePreview;
    }

    if (window.rolesUser.includes("MANAGEMENT")) {
        toggleResourcesFields("educational-resource-form", false);
    } else if (
        ["INTRODUCTION", "UNDER_CORRECTION_APPROVAL"].includes(
            resource.status.code
        )
    ) {
        toggleResourcesFields("educational-resource-form", false);
    } else {
        toggleResourcesFields("educational-resource-form", true);
    }

    if (resource.contact_emails) {
        resource.contact_emails.forEach((contact_email) => {
            tomSelectContactEmails.addOption({
                value: contact_email.email,
                text: contact_email.email,
            });
            tomSelectContactEmails.addItem(contact_email.email);
        });
    }
}

function selectLearningResultsUser(learningResultsUserSelected = []) {
    function openNode(node) {
        if (node.id) {
            treeCompetencesLearningResults.openNode(node);
            openNode(node.parent);
        }
    }

    learningResultsUserSelected.forEach((learningResult) => {
        const n = treeCompetencesLearningResults.getNodeById(
            learningResult.uid
        );

        if (n) {
            openNode(n);
            treeCompetencesLearningResults.checkNode(n, true);
        }

        selectedLearningResults.add(learningResult.uid);
    });

    document.getElementById("learning-results-counter").innerText =
        selectedLearningResults.size;
}

function toggleResourcesFields(formId, isDisabled) {
    if (isDisabled) {
        tomSelectTags.disable();
        tomSelectCategories.disable();
    } else {
        tomSelectTags.enable();
        tomSelectCategories.enable();
    }

    document.getElementById(
        "tree-competences-learning-results-disabled"
    ).value = isDisabled ? "1" : "0";

    toggleFormFields(formId, isDisabled);

    if (isDisabled) disableInfiniteTreeCheckboxes();
}

function disableInfiniteTreeCheckboxes() {
    const infiniteTreeCheckboxes = document.querySelectorAll(
        ".infinite-tree-checkbox"
    );
    infiniteTreeCheckboxes.forEach(function (checkbox) {
        checkbox.disabled = true;
    });
}

/**
 * Elimina usuarios seleccionados.
 * Realiza una petición DELETE al servidor y actualiza la tabla si tiene éxito.
 */
async function deleteResources() {
    const params = {
        url: "/learning_objects/educational_resources/delete_resources",
        method: "DELETE",
        body: { resourcesUids: selectedResources.map((e) => e.uid) },
        toast: true,
        loader: true,
        stringify: true,
    };

    apiFetch(params).then(() => {
        resourcesTable.replaceData(endPointTable);
    });
}

/**
 * Este bloque maneja la presentación del formulario para una nueva llamada.
 * Recopila los datos y los envía a un endpoint específico.
 * Si la operación tiene éxito, actualiza la tabla y muestra un toast.
 */
function submitEducationalResource(event) {
    const action = event.submitter.value;
    resetFormErrors("educational-resource-form");
    const formData = new FormData(this);

    const tags = tomSelectTags.items;
    formData.append("tags", JSON.stringify(tags));

    const metadata = JSON.stringify(getMetadataArray());
    formData.append("metadata", metadata);

    const categories = tomSelectCategories.items;
    formData.append("categories", JSON.stringify(categories));

    formData.append("action", action);

    const contactEmails = tomSelectContactEmails.items;
    formData.append("contact_emails", JSON.stringify(contactEmails));

    formData.append(
        "learning_results",
        JSON.stringify([...selectedLearningResults])
    );
    const params = {
        url: "/learning_objects/educational_resources/save_resource",
        method: "POST",
        body: formData,
        toast: true,
        loader: true,
    };

    apiFetch(params)
        .then(() => {
            hideModal("educational-resource-modal");
            resourcesTable.replaceData(endPointTable);
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

/**
 * Reinicia el estado del modal y del formulario.
 */
function resetModal() {
    const form = document.getElementById("educational-resource-form");
    form.reset();
    document.getElementById("field-created-by").classList.add("hidden");
    document.getElementById("field-has-embeddings").classList.add("hidden");
    document.getElementById("educational_resource_uid").value = "";
    resetFormErrors();

    const urlResourceElement = document.getElementById("url-resource");
    urlResourceElement.classList.add("hidden");
    document.getElementById("url-resource").href = "javascript:void(0)";
    urlResourceElement.innerText = "";

    document.querySelectorAll(".file-name").forEach((element) => {
        element.textContent = "Ningún archivo seleccionado";
    });

    const resourceImageElement = document.getElementById("image_path_preview");
    resourceImageElement.src = defaultImagePreview;
    document.getElementById("url_container").classList.add("hidden");
    document.getElementById("resource_file_container").classList.add("hidden");
    document.getElementById("image-name").innerHTML = "";

    const metadataContainer = document.getElementById("metadata-container");
    metadataContainer.innerHTML = "";
    metadataCounter = 0;

    tomSelectTags.clear();
    tomSelectCategories.clear();
    tomSelectContactEmails.clear();
    selectedLearningResults = new Set();
    instanceTreeCompetencesLearningResults();
}

function changeStatusesResources() {
    if (!selectedResources.length) {
        showToast("No has seleccionado ningún recurso", "error");

        return;
    }

    let resourcesList = document.getElementById("resources-list");
    resourcesList.innerHTML = "";

    selectedResources.forEach((resource) => {
        const status = resource.status.code;

        let optionsStatuses = [];
        if (status === "PENDING_APPROVAL") {
            optionsStatuses = [
                {
                    label: "Publicado",
                    value: "PUBLISHED",
                },
                {
                    label: "Rechazado",
                    value: "REJECTED",
                },
                {
                    label: "En subsanación para aprobación",
                    value: "UNDER_CORRECTION_APPROVAL",
                },
            ];
        } else if (status === "UNDER_CORRECTION_APPROVAL") {
            optionsStatuses = [
                {
                    label: "Publicado",
                    value: "PUBLISHED",
                },
                {
                    label: "Rechazado",
                    value: "REJECTED",
                },
            ];
        } else if (
            [
                "PUBLISHED",
                "REJECTED",
                "UNDER_CORRECTION_APPROVAL",
                "PENDING_PUBLICATION",
                "PENDING_APPROVAL",
            ].includes(status)
        ) {
            optionsStatuses.push({
                label: "Publicado",
                value: "PUBLISHED",
            });

            optionsStatuses.push({
                label: "Rechazado",
                value: "REJECTED",
            });

            optionsStatuses.push({
                label: "En subsanación para aprobación",
                value: "UNDER_CORRECTION_APPROVAL",
            });
        } else {
            optionsStatuses = [];
        }

        // Se podrá retirar un recurso en cualquier estado
        optionsStatuses.push({
            label: "Retirado",
            value: "RETIRED",
        });

        // Excluímos el estado actual
        optionsStatuses = optionsStatuses.filter(
            (option) => option.value !== status
        );

        resourcesList.innerHTML += `
                <div class="mb-5 bg-gray-100 p-4 rounded-xl">
                <h4>${resource.title}</h4>

                <div class="resource px-4" data-uid="${resource.uid}">

                    <div class="poa-form">

                    </div>

                    <select class="status-resource poa-select mb-2 min-w-[250px]">
                        <option value="" selected>Selecciona un estado</option>
                        ${optionsStatuses.map((option) => {
                            return `<option value="${option.value}">${option.label}</option>`;
                        })}
                    </select>
                    <div class="">
                        <h4>Indica un motivo</h4>
                        <textarea maxlength="1000" placeholder="El estado del recurso se debe a..." class="reason-status-resource poa-input"></textarea>
                    </div>
                </div>
            </div>`;
    });

    showModal("change-statuses-resources-modal", "Cambiar estado de recursos");
}

/**
 * Envía los cambios de estado de los cursos seleccionados.
 * Recoge los nuevos estados y los motivos de cambio de cada curso y realiza una petición 'fetch' para actualizarlos.
 */
function submitChangeStatusesResources() {
    const changesResourcesStatuses = getResourcesStatuses();

    const params = {
        url: "/learning_objects/educational_resources/change_statuses_resources",
        method: "POST",
        body: {
            changesResourcesStatuses,
        },
        toast: true,
        stringify: true,
        loader: true,
    };

    apiFetch(params).then(() => {
        hideModal("change-statuses-resources-modal");
        resourcesTable.replaceData(endPointTable);
    });
}

function getResourcesStatuses() {
    const resourceContainer = document.getElementById("resources-list");
    const resourceDivs = resourceContainer.querySelectorAll("div.resource");
    const changesResourcesStatuses = [];

    resourceDivs.forEach((resourceElement) => {
        const uid = resourceElement.getAttribute("data-uid");
        const statusElement = resourceElement.querySelector(".status-resource");

        const status = statusElement.value;

        const reasonElement = resourceElement.querySelector(
            ".reason-status-resource"
        );

        const reason = reasonElement.value;

        // Cambia esta condición si necesitas que ambos campos no sean obligatorios
        changesResourcesStatuses.push({
            uid,
            status,
            reason,
        });
    });

    return changesResourcesStatuses;
}

function generateTags() {
    const text = document.getElementById("description").value;

    if (!text) {
        showToast("No hay descripción para generar etiquetas", "error");
        return;
    }

    const params = {
        url: "/learning_objects/generate_tags",
        method: "POST",
        body: { text },
        loader: true,
        stringify: true,
    };

    apiFetch(params)
        .then((data) => {
            data.forEach((tag) => {
                tomSelectTags.addOption({ value: tag, text: tag });
                tomSelectTags.addItem(tag);
            });
        })
        .catch(() => {
            showToast("No se han podido generar las etiquetas", "error");
        });
}

function generateMetadata() {
    const text = document.getElementById("description").value;

    if (!text) {
        showToast("No hay descripción para generar metadatos", "error");
        return;
    }

    const params = {
        url: "/learning_objects/generate_metadata",
        method: "POST",
        body: { text },
        loader: true,
        stringify: true,
    };

    apiFetch(params)
        .then((data) => {
            data.forEach((metadata) => {
                addMetadataPair(metadata);
            });
        })
        .catch(() => {
            showToast("No se han podido generar los metadatos", "error");
        });
}

function instanceTreeCompetencesLearningResults() {
    const updateCheckboxState = (treeCompetencesLearningResults) => {
        const checkboxes =
            treeCompetencesLearningResults.contentElement.querySelectorAll(
                'input[type="checkbox"]'
            );

        let treeDisabled =
            document.getElementById(
                "tree-competences-learning-results-disabled"
            ).value === "1";

        // Si el bloque está deshabilitado, deshabilitamos todos los checkboxes
        for (const checkbox of checkboxes) {
            checkbox.indeterminate = checkbox.hasAttribute("data-indeterminate");

            if (treeDisabled) {
                checkbox.disabled = true;
            }
        }
    };

    if (treeCompetencesLearningResults) treeCompetencesLearningResults.clear();
    document.getElementById("tree-competences-learning-results").innerHTML = "";

    treeCompetencesLearningResults = new InfiniteTree(
        document.getElementById("tree-competences-learning-results"),
        {
            rowRenderer: renderer,
            togglerClass: "infinite-tree-toggler-svg",
            shouldSelectNode: (node) => {
                return false;
            },
            noDataText: "No hay ningún marco de competencias",
        }
    );

    treeCompetencesLearningResults.on("click", (event) => {
        const currentNode = treeCompetencesLearningResults.getNodeFromPoint(
            event.clientX,
            event.clientY
        );

        if (!currentNode || !event.target.classList.contains("checkbox"))
            return;
        event.stopPropagation();
        treeCompetencesLearningResults.checkNode(currentNode);

        // Llamada a la función con el nodo actual
        updateSelectedCompetencesAndLearningResults(currentNode);
    });

    treeCompetencesLearningResults.on("contentDidUpdate", () => {
        updateCheckboxState(treeCompetencesLearningResults);
    });

    treeCompetencesLearningResults.on("clusterDidChange", () => {
        updateCheckboxState(treeCompetencesLearningResults);
    });

    let competencesLearningResultsCopy = JSON.parse(
        JSON.stringify(window.competencesLearningResults)
    );

    treeCompetencesLearningResults.loadData(competencesLearningResultsCopy);
}

function updateSelectedCompetencesAndLearningResults(currentNode) {
    function getChildNodesLearningResults(node, resultSet = new Set()) {
        if (!node.children.length) return resultSet;

        node.children.forEach((child) => {
            if (child.type === "learning_result") {
                resultSet.add(child.id);
            }
            getChildNodesLearningResults(child, resultSet);
        });

        return resultSet;
    }

    const { id, state } = currentNode;
    const isSelected = state.checked;

    function updateSet(set, items, add) {
        if (add) {
            set.add(id);
            items.forEach((item) => set.add(item));
        } else {
            set.delete(id);
            items.forEach((item) => set.delete(item));
        }

        document.getElementById("learning-results-counter").innerText =
            set.size;
        return set;
    }

    const childLearningResults = getChildNodesLearningResults(currentNode);

    // Convertir selectedLearningResults a Set si aún no lo es
    if (!(selectedLearningResults instanceof Set)) {
        selectedLearningResults = new Set(selectedLearningResults);
    }

    selectedLearningResults = updateSet(
        selectedLearningResults,
        Array.from(childLearningResults),
        isSelected
    );

    if (currentNode.type === "learning_result") {
        selectedLearningResults = updateSet(
            selectedLearningResults,
            [id],
            isSelected
        );
    }
}

function getStatusEducationalResourceColor(statusCode) {
    const statusColors = {
        INTRODUCTION: "#EBEBF4",
        PENDING_APPROVAL: "#EDF4FB",
        UNDER_CORRECTION_APPROVAL: "#F4EBEB",
        PUBLISHED: "#EBF3F4",
        RETIRED: "#FDF5FE",
        REJECTED: "#F4EBF0",
    };

    return statusColors[statusCode];
}
