import {
    hideModal,
    showModal,
    showModalConfirmation,
} from "../modal_handler.js";
import {
    controlsPagination,
    updatePaginationInfo,
    tabulatorBaseConfig,
    controlsSearch,
    moreOptionsBtn,
    dropdownMenu,
    updateArrayRecords,
    handleHeaderClick,
    formatDateTime,
    getPaginationControls,
} from "../tabulator_handler.js";
import {
    getCsrfToken,
    showFormErrors,
    resetFormErrors,
    updateInputImage,
    getFlatpickrDateRangeSql,
    getMultipleTomSelectInstance,
    dropdownButtonToogle,
    instanceFlatpickr,
    getFlatpickrDateRange,
    apiFetch,
    setDisabledSpecificDivFields,
    setReadOnlyForSpecificFields,
    setDisabledSpecificFormFields,
    updateInputValuesSelects,
    getLiveSearchTomSelectInstance,
    getOptionsSelectedTomSelectInstance,
    checkParamInUrl,
    wipeParamsInUrl,
    getMultipleFreeEmailsTomSelectInstance,
    getMultipleFreeTomSelectInstance,
    fillFormWithObject,
    changeColorColoris,
    updateInputFile,
} from "../app.js";
import { heroicon } from "../heroicons.js";
import { TabulatorFull as Tabulator } from "tabulator-tables";
import { showToast } from "../toast.js";
import Treeselect from "treeselectjs";
import InfiniteTree from "infinite-tree";
import renderer from "../renderer_infinite_tree.js";

let selectedCourses = [];
let selectedCourseStudents = [];
let coursesTable;
let courseStudensTable;

const endPointTable = "/learning_objects/courses/get_courses";
const endPointStudentTable = "/learning_objects/courses/get_course_students";
const coursesTableId = "courses-table";

let tomSelectTags;

let tomSelectNoCoordinatorsTeachers;
let tomSelectCoordinatorsTeachers;

let tomSelectCategories;
let tomSelectContactEmails;

let tomSelectCategoriesFilter;

let tomSelectNoCoordinatorsTeachersFilter;
let tomSelectCoordinatorsTeachersFilter;

let tomSelectCreatorsFilter;
let tomSelectCourseStatusesFilter;
let tomSelectCallsFilter;
let tomSelectEducationalProgramsFilter;
let tomSelectCourseTypesFilter;

let flatpickrInscriptionDate;
let flatpickrRealizationDate;

let filters = [];

let selectedCompetencesFilter = [];
let competencesTreeSelect;

let selectedCourseUid = null;

let tomSelectUsersToEnroll;

let competencesLearningResults = [];

class Trees {
    constructor(order, tree, selectedNodes = []) {
        this.order = order;
        this.tree = tree;
        this.selectedNodes = new Set(selectedNodes);
    }

    // Mapa estático para almacenar instancias
    static instances = new Map();

    // Método estático para crear y almacenar una instancia
    static storeInstance(order, tree, selectedNodes = []) {
        const instance = new Trees(order, tree, selectedNodes);
        Trees.instances.set(order, instance); // Corregido: eliminar el tercer argumento
        return instance;
    }

    // Método estático para obtener una instancia por su order
    static getInstance(order) {
        return Trees.instances.get(order);
    }

    // Método para agregar un nodo seleccionado
    addSelectedNode(node) {
        this.selectedNodes.add(node);
    }

    // Método para obtener todos los nodos seleccionados
    getSelectedNodes() {
        return Array.from(this.selectedNodes);
    }

    // Método estático para agregar un nodo seleccionado por order
    static addSelectedNodeByOrder(order, node) {
        const instance = Trees.getInstance(order);
        if (instance) {
            instance.addSelectedNode(node);
        }
    }

    static addNodesSelectedByOrder(order, nodes) {
        const instance = Trees.getInstance(order);
        if (instance) {
            nodes.forEach((node) => instance.addSelectedNode(node));
        }
    }

    // Método estático para obtener nodos seleccionados por order
    static getSelectedNodesByOrder(order) {
        const instance = Trees.getInstance(order);
        if (instance) {
            return instance.getSelectedNodes();
        }
        return []; // Devolver un array vacío si no se encuentra la instancia
    }

    // Método para eliminar un nodo seleccionado
    deleteSelectedNode(node) {
        this.selectedNodes.delete(node);
    }

    // Método estático para eliminar un nodo seleccionado por order
    static deleteSelectedNodeByOrder(order, node) {
        const instance = Trees.getInstance(order);
        if (instance) {
            instance.deleteSelectedNode(node);
        }
    }

    // Método estático para eliminar varios nodos seleccionados por order
    static deleteSelectedNodesByOrder(order, nodes) {
        const instance = Trees.getInstance(order);
        if (instance) {
            nodes.forEach((node) => instance.deleteSelectedNode(node));
        }
    }
}

document.addEventListener("DOMContentLoaded", async function () {
    initHandlers();
    updateInputFile();
    initializeCoursesTable();
    controlsHandlerModalCourse();
    initializeTomSelect();
    updateInputImage();
    initializeFlatpickrDates();
    initializeTreeSelect();
    controlChecksCarrousels();
    dropdownButtonToogle();
    controlValidationStudents();
    controlEnrollingDates();
    controlsCompositionCourse();
    updateInputValuesSelects();

    syncTomSelectsTeachers();

    // Si recibimos un uid en la URL, cargamos el curso
    const courseUid = checkParamInUrl("uid");
    wipeParamsInUrl();
    if (courseUid) loadCourseModal(courseUid);

    await loadCompetencesLearningResults();
});

async function loadCompetencesLearningResults() {
    if (competencesLearningResults.length) return;

    const params = {
        url: "/learning_objects/courses/get_all_competences",
        method: "GET",
        loader: true,
    };

    function mapStructure(obj, isMultiSelect) {
        // Crear un nuevo objeto con los campos necesarios
        const mappedObj = {
            id: obj.uid,
            name: obj.name,
            isMultiSelect: isMultiSelect ? true : false,
            children: [],
            type: "competence",
            showCheckbox: true,
            disabled: isMultiSelect ? false : true,
        };

        // Si hay subcompetencias, recursivamente mapéalas
        if (obj.subcompetences && obj.subcompetences.length > 0) {
            obj.subcompetences.forEach((sub) => {
                mappedObj.children.push(mapStructure(sub, isMultiSelect));
            });
        }

        // Si hay resultados de aprendizaje, agrégalos también
        if (obj.learning_results && obj.learning_results.length > 0) {
            obj.learning_results.forEach((lr) => {
                mappedObj.children.push({
                    id: lr.uid,
                    name: lr.name,
                    type: "learning_result",
                    showCheckbox: true,
                    isMultiSelect: isMultiSelect,
                    disabled: false,
                });
            });
        }

        // Devuelve el objeto mapeado
        return mappedObj;
    }

    const data = await apiFetch(params);

    const structure = [];

    data.forEach((competenceFramework) => {
        structure.push(
            mapStructure(
                competenceFramework,
                competenceFramework.is_multi_select
            )
        );
    });

    competencesLearningResults = structure;
}

async function filterTree(textToSearch, orderTree) {
    const treeToFilter = Trees.getInstance(Number(orderTree)).tree;

    closeAllNodes(treeToFilter.getRootNode());

    treeToFilter.filter((node) => {
        const name = node.name || "";
        const matchesSearch = name
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .toLowerCase()
            .includes(
                textToSearch
                    .normalize("NFD")
                    .replace(/[\u0300-\u036f]/g, "")
                    .toLowerCase()
            );
        if (matchesSearch) {
            openNode(node);
        }

        return matchesSearch;
    });

    function openNode(node) {
        if (node.id) {
            treeToFilter.openNode(node);
            openNode(node.parent);
        }
    }

    function closeAllNodes(node) {
        node.children.forEach((child) => {
            closeAllNodes(child);
            if (child.state.open) treeToFilter.closeNode(child);
        });
    }
}

async function instanceTreeCompetences(order, selectedNodes = []) {
    const updateCheckboxState = (tree) => {
        const checkboxes = tree.contentElement.querySelectorAll(
            'input[type="checkbox"]'
        );

        // Si el bloque está deshabilitado, deshabilitamos todos los checkboxes
        const treeDisabled = document
            .getElementById("course-composition-block")
            .getAttribute("data-disabled");

        for (let i = 0; i < checkboxes.length; ++i) {
            const checkbox = checkboxes[i];
            if (checkbox.hasAttribute("data-indeterminate")) {
                checkbox.indeterminate = true;
            } else {
                checkbox.indeterminate = false;
            }

            if (treeDisabled === "1") checkbox.disabled = true;
        }
    };

    var tree = new InfiniteTree(
        document.querySelector(`.competences-section[data-order="${order}"]`),
        {
            rowRenderer: renderer,
            selectable: false,
            noDataText:
                "No se ha encontrado ninguna competencia ni resultado de aprendizaje",
        }
    );

    tree.on("click", (event) => {
        const currentNode = tree.getNodeFromPoint(event.clientX, event.clientY);
        if (!currentNode || event.target.className !== "checkbox") return;
        event.stopPropagation();
        tree.checkNode(currentNode);

        // si el padre NO es multiselect, desseleccionamos el resto de sus hermanos
        if (!currentNode.parent.isMultiSelect) {
            currentNode.parent.children.forEach((child) => {
                if (
                    child !== currentNode &&
                    child.state.checked &&
                    !child.children.length
                ) {
                    tree.checkNode(child);
                }
            });
        }

        function getChildNodesLearningResults(node) {
            let resultArray = [];

            if (!node.children.length) return resultArray;

            node.children.forEach((child) => {
                // Si el nodo cumple con la condición, añadir al array
                if (child.type === "learning_result") {
                    resultArray.push(child.id);
                }

                resultArray = resultArray.concat(
                    getChildNodesLearningResults(child)
                );
            });

            return resultArray;
        }

        const childNodesLearningResults =
            getChildNodesLearningResults(currentNode);

        if (currentNode.state.checked) {
            if (currentNode.isMultiSelect) {
                Trees.addNodesSelectedByOrder(order, childNodesLearningResults);
            }
            // Si no es multi seleccionable, deseleccionamos el resto de nodos hermanos
            else {
                const parentNode = currentNode.parent;
                parentNode.children.forEach((child) => {
                    if (child !== currentNode) {
                        tree.checkNode(child, false);
                        Trees.deleteSelectedNodeByOrder(order, child.id);
                    }
                });
            }

            if (currentNode.type === "learning_result")
                Trees.addSelectedNodeByOrder(order, currentNode.id);
        } else {
            childNodesLearningResults.push(currentNode.id);
            Trees.deleteSelectedNodesByOrder(order, childNodesLearningResults);
        }
    });

    tree.on("contentDidUpdate", () => {
        updateCheckboxState(tree);
    });

    tree.on("clusterDidChange", () => {
        updateCheckboxState(tree);
    });

    // Es importante pasarle una copia del array para que no se modifique el original
    let competencesLearningResultsCopy = JSON.parse(
        JSON.stringify(competencesLearningResults)
    );
    tree.loadData(competencesLearningResultsCopy);
    function openNode(node) {
        if (node.id) {
            tree.openNode(node);
            openNode(node.parent);
        }
    }

    selectedNodes.forEach((node) => {
        const n = tree.getNodeById(node);
        if (n) {
            openNode(n);
            tree.checkNode(n, true);
        }
    });

    return tree;
}

function initializeTreeSelect() {
    const optionsCompetencesTreeSelect = convertCompetencesToOptions(
        window.competences
    );

    competencesTreeSelect = new Treeselect({
        parentHtmlContainer: document.getElementById("treeselect-competences"),
        options: optionsCompetencesTreeSelect,
        showTags: false,
        tagsCountText: "competencias seleccionadas",
        searchable: false,
        iconElements: {},
        placeholder: "Seleccione competencias",
        value: [],
        inputCallback: function (competences) {
            selectedCompetencesFilter = competences;
        },
    });
}

function convertCompetencesToOptions(competences) {
    return competences.map((competence) => ({
        name: competence.name,
        value: competence.uid,
        children: convertCompetencesToOptions(competence.subcompetences),
    }));
}

function initHandlers() {
    document
        .getElementById("confirm-change-statuses-btn")
        .addEventListener("click", submitChangeStatusesCourses);

    document
        .getElementById("approve-students-btn")
        .addEventListener("click", function () {
            if (selectedCourseStudents.length) {
                showModalConfirmation(
                    "Aprobación de inscripciones",
                    "¿Estás seguro de que quieres aprobar las inscripciones de los estudiantes seleccionados?"
                ).then((result) => {
                    if (!result) return;
                    approveStudentsCourse();
                });
            }
        });

    document
        .getElementById("reject-students-btn")
        .addEventListener("click", function () {
            if (selectedCourseStudents.length) {
                showModalConfirmation(
                    "Anulación de inscripciones",
                    "¿Estás seguro de que quieres rechazar las inscripciones de los estudiantes seleccionados?"
                ).then((result) => {
                    if (!result) return;
                    rejectStudentsCourse();
                });
            }
        });

    document
        .getElementById("filter-courses-btn")
        .addEventListener("click", function () {
            showModal("filter-courses-modal");
        });

    document
        .getElementById("course-form")
        .addEventListener("submit", submitFormCourseModal);

    document
        .getElementById("payment_mode")
        .addEventListener("change", function (e) {
            const paymentMode = e.target.value;
            updatePaymentMode(paymentMode);
        });

    const changeStatusesBtn = document.getElementById("change-statuses-btn");

    if (changeStatusesBtn) {
        changeStatusesBtn.addEventListener("click", function () {
            changeStatusesCourses();
        });
    }

    document
        .getElementById("btn-add-document")
        .addEventListener("click", function () {
            addDocument();
        });

    document
        .getElementById("btn-add-payment")
        .addEventListener("click", function () {
            addPaymentTerm();
        });

    document
        .querySelector(".document-list")
        .addEventListener("click", removeDocument);

    document
        .getElementById("payment-terms-list")
        .addEventListener("click", removePaymentTerm);

    document
        .getElementById("delete-all-filters")
        .addEventListener("click", function () {
            resetFilters();
        });

    document
        .getElementById("filter-btn")
        .addEventListener("click", function () {
            controlSaveHandlerFilters();
        });

    document
        .getElementById("enroll-students-btn")
        .addEventListener("click", function () {
            showModal("enroll-course-modal");
            if (tomSelectUsersToEnroll) tomSelectUsersToEnroll.destroy();
            tomSelectUsersToEnroll = getLiveSearchTomSelectInstance(
                "#enroll_students",
                "/users/list_users/search_users_no_enrolled/" +
                    selectedCourseUid +
                    "/",
                function (entry) {
                    return {
                        value: entry.uid,
                        text: `${entry.first_name} ${entry.last_name}`,
                    };
                }
            );
        });

    document
        .getElementById("enroll-btn")
        .addEventListener("click", function () {
            enrollStudentsToCourse();
        });

    document
        .getElementById("enroll-students-csv-btn")
        .addEventListener("click", function () {
            showModal("enroll-course-csv-modal");
        });

    document
        .getElementById("enroll-course-csv-btn")
        .addEventListener("click", function () {
            enrollStudentsCsv();
        });

    document
        .getElementById("previsualize-slider")
        .addEventListener("click", function () {
            previsualizeSlider();
        });

    document
        .getElementById("belongs_to_educational_program")
        .addEventListener("change", function (e) {
            var isChecked = e.target.checked;
            setVisibilityCourseFieldsBasedOnProgramMembership(
                isChecked ? true : false
            );
        });

    document
        .getElementById("btn-reload-table")
        .addEventListener("click", function () {
            reloadTableCourses();
        });

    // TODO
    document
        .getElementById("send-credentials-students-btn")
        .addEventListener("click", function () {
            showModalConfirmation(
                "Envío de credenciales",
                "¿Estás seguro de que quieres enviar las credenciales a los estudiantes seleccionados?"
            ).then((result) => {});
        });

    let typingTimer; // Almacena el temporizador de espera
    const doneTypingInterval = 500;

    document.addEventListener("input", function (e) {
        if (e.target.matches('input[type="text"].search-tree')) {
            clearTimeout(typingTimer); // Limpia el temporizador anterior para reiniciar el retraso
            typingTimer = setTimeout(function () {
                filterTree(e.target.value, e.target.dataset.order); // Ejecuta después del retraso
            }, doneTypingInterval);
        }
    });

    const generateTagsBtn = document.getElementById("generate-tags-btn");
    if (generateTagsBtn) {
        generateTagsBtn.addEventListener("click", () => {
            if (!generateTagsBtn.classList.contains("element-disabled")) {
                generateTags();
            }
        });
    }
}

/**
 * Esta función se utiliza para previsualizar el slider del curso antes de guardarlo.
 * Recoge el archivo de imagen y los valores de los campos de título, descripción y color,
 * y posteriormente redirecciona al front pasándole por parámetro el uid de previsualización.
 */
function previsualizeSlider() {
    let fileInput = document.getElementById(
        "featured_big_carrousel_image_path"
    );
    let file = fileInput.files[0];

    let formData = new FormData();
    formData.append("image", file);
    formData.append(
        "title",
        document.getElementById("featured_big_carrousel_title").value
    );
    formData.append(
        "description",
        document.getElementById("featured_big_carrousel_description").value
    );
    formData.append(
        "color",
        document.getElementById("featured_slider_color_font").value
    );

    formData.append("course_uid", document.getElementById("course_uid").value);

    const params = {
        url: "/sliders/save_previsualization",
        method: "POST",
        body: formData,
        toast: true,
        stringify: false,
        loader: true,
    };

    apiFetch(params).then((data) => {
        // Abrir en nueva pestaña
        let previsualizationUrl = `${window.frontUrl}?previsualize-slider=${data.previsualizationUid}`;
        window.open(previsualizationUrl, "_blank");
    });
}

function addDocument() {
    const template = document
        .getElementById("document-template")
        .content.cloneNode(true);
    document.getElementById("document-list").appendChild(template);
}

function removeDocument(event) {
    let target = event.target;

    if (!target.classList.contains("btn-remove-document")) {
        target = target.closest(".btn-remove-document");
    }

    if (target) {
        target.closest(".document").remove();
    }
}

/**
 * Maneja el controlador de composición de un curso
 */
function controlsCompositionCourse() {
    document.getElementById("addBlock").addEventListener("click", addBlock);

    document
        .getElementById("course-composition")
        .addEventListener("click", function (e) {
            if (e.target.matches(".removeBlock")) {
                removeBlock(e.target);
            } else if (e.target.matches(".addSubBlock")) {
                addSubBlock(e.target);
            } else if (e.target.matches(".removeSubBlock")) {
                removeSubBlock(e.target);
            } else if (e.target.matches(".addElement")) {
                addElement(e.target);
            } else if (e.target.matches(".removeElement")) {
                removeElement(e.target);
            } else if (e.target.matches(".addSubElement")) {
                addSubElement(e.target);
            } else if (e.target.matches(".removeSubElement")) {
                removeSubElement(e.target);
            }
        });

    async function addBlock() {
        const blockTemplate = document.getElementById("block-template").content;
        const newBlock = blockTemplate.cloneNode(true);

        let newBlockElement = newBlock.querySelector(".block");

        const composition = document
            .getElementById("course-composition")
            .appendChild(newBlock);

        const blockOrder = document.querySelectorAll(
            "#course-composition .block"
        ).length;

        newBlockElement.dataset.order = blockOrder;

        newBlockElement.querySelector(".competences-section").dataset.order =
            blockOrder;

        newBlockElement.querySelector(".search-tree").dataset.order =
            blockOrder;
        let tree = await instanceTreeCompetences(blockOrder);
        Trees.storeInstance(blockOrder, tree, []);

        return composition.querySelector("button.removeBlock");
    }

    function removeBlock(button) {
        button.closest(".block").remove();
    }

    function addSubBlock(button) {
        const subBlockTemplate =
            document.getElementById("sub-block-template").content;
        const newSubBlock = subBlockTemplate.cloneNode(true);
        let newSubBlockElement = newSubBlock.querySelector(".sub-block");
        const subBlock = button
            .closest(".block")
            .querySelector(".sub-blocks")
            .appendChild(newSubBlock);

        const subBlockOrder = document.querySelectorAll(
            "#course-composition .sub-block"
        ).length;

        newSubBlockElement.dataset.order = subBlockOrder;

        return subBlock.querySelector("button.removeSubBlock");
    }

    function removeSubBlock(button) {
        button.closest(".sub-block").remove();
    }

    function addSubElement(button) {
        const subElementTemplate = document.getElementById(
            "sub-element-template"
        ).content;
        const newSubElement = subElementTemplate.cloneNode(true);
        let newSubElementElement = newSubElement.querySelector(".sub-element");
        const subElement = button
            .closest(".element")
            .querySelector(".sub-elements")
            .appendChild(newSubElement);

        const subElementOrder = document.querySelectorAll(
            "#course-composition .sub-element"
        ).length;
        newSubElementElement.dataset.order = subElementOrder;

        return subElement.querySelector("button.removeSubElement");
    }

    function addElement(button) {
        const elementTemplate =
            document.getElementById("element-template").content;
        const newElement = elementTemplate.cloneNode(true);
        let newElementElement = newElement.querySelector(".element");
        const element = button
            .closest(".sub-block")
            .querySelector(".elements")
            .appendChild(newElement);

        const elementOrder = document.querySelectorAll(
            "#course-composition .element"
        ).length;

        newElementElement.dataset.order = elementOrder;
        return element.querySelector("button.removeElement");
    }

    function removeElement(button) {
        button.closest(".element").remove();
    }

    function removeSubElement(button) {
        button.closest(".sub-element").remove();
    }
}

function getStructureCourseJSON() {
    const course = [];
    const blocks = document.querySelectorAll("#course-composition .block");

    blocks.forEach((block) => {
        const blockOrder = parseInt(block.dataset.order);
        const blockObj = {
            type: block.querySelector(".block-type").value,
            name: block.querySelector(".block-name").value,
            description: block.querySelector(".block-description").value,
            uid: block.dataset.uid ?? null,
            order: blockOrder,
            learningResults: Trees.getSelectedNodesByOrder(blockOrder),
        };

        const subBlocks = block.querySelectorAll(".sub-block");

        if (subBlocks.length) blockObj.subBlocks = [];

        subBlocks.forEach((subBlock) => {
            const subBlockObj = {
                name: subBlock.querySelector(".sub-block-name").value,
                description: subBlock.querySelector(".sub-block-description")
                    .value,
                uid: subBlock.dataset.uid ?? null,
                order: subBlock.dataset.order,
            };

            const elements = subBlock.querySelectorAll(".element");

            if (elements.length) subBlockObj.elements = [];
            elements.forEach((element) => {
                const elementObj = {
                    name: element.querySelector(".element-name").value,
                    description: element.querySelector(".element-description")
                        .value,
                    uid: element.dataset.uid ?? null,
                    order: element.dataset.order,
                };

                const subElements = element.querySelectorAll(".sub-element");
                if (subElements.length) elementObj.subElements = [];
                subElements.forEach((subElement) => {
                    const subElementObj = {
                        name: subElement.querySelector(".sub-element-name")
                            .value,
                        description: subElement.querySelector(
                            ".sub-element-description"
                        ).value,
                        uid: subElement.dataset.uid ?? null,
                        order: subElement.dataset.order,
                    };
                    elementObj.subElements.push(subElementObj);
                });

                subBlockObj.elements.push(elementObj);
            });

            blockObj.subBlocks.push(subBlockObj);
        });

        course.push(blockObj);
    });

    return JSON.stringify(course, null);
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

    if (filterKey == "calls") tomSelectCallsFilter.clear();
    else if (filterKey == "course_statuses")
        tomSelectCourseStatusesFilter.clear();
    else if (filterKey == "educational_programs")
        tomSelectEducationalProgramsFilter.clear();
    else if (filterKey == "course_types") tomSelectCourseTypesFilter.clear();
    else if (filterKey == "creators") tomSelectCreatorsFilter.clear();
    else if (filterKey == "filter_inscription_date")
        flatpickrInscriptionDate.clear();
    else if (filterKey == "filter_realization_date")
        flatpickrRealizationDate.clear();
    else if (filterKey == "filter_competences") {
        selectedCompetencesFilter = [];
        competencesTreeSelect.updateValue([]);
    } else if (filterKey == "coordinators_teachers")
        tomSelectCoordinatorsTeachersFilter.clear();
    else if (filterKey == "no_coordinators_teachers")
        tomSelectNoCoordinatorsTeachersFilter.clear();
    else document.getElementById(filterKey).value = "";

    showFilters();
    initializeCoursesTable();
}

function resetFilters() {
    filters = [];
    showFilters();
    initializeCoursesTable();

    tomSelectCallsFilter.clear();
    tomSelectCourseStatusesFilter.clear();
    tomSelectCourseTypesFilter.clear();
    tomSelectCreatorsFilter.clear();
    flatpickrInscriptionDate.clear();
    flatpickrRealizationDate.clear();
    tomSelectNoCoordinatorsTeachersFilter.clear();
    tomSelectCoordinatorsTeachersFilter.clear();
    competencesTreeSelect.updateValue([]);
    tomSelectCategoriesFilter.clear();
    tomSelectEducationalProgramsFilter.clear();

    document.getElementById("filter_min_ects_workload").value = "";
    document.getElementById("filter_max_ects_workload").value = "";
    document.getElementById("filter_min_cost").value = "";
    document.getElementById("filter_max_cost").value = "";
    document.getElementById("filter_min_required_students").value = "";
    document.getElementById("filter_max_required_students").value = "";
    document.getElementById("filter_center").value = "";
}

/**
 * Inicializa los controles de fecha 'flatpickr' para los filtros de fecha de inicio
 * y fecha de fin, configurando el formato y el idioma en español.
 */
function initializeFlatpickrDates() {
    flatpickrInscriptionDate = instanceFlatpickr("filter_inscription_date");
    flatpickrRealizationDate = instanceFlatpickr("filter_realization_date");
}

/**
 * Maneja el evento de clic en el botón para aplicar los filtros.
 * Recoge los filtros del modal, los muestra en la interfaz y vuelve a inicializar
 * la tabla de cursos con los nuevos filtros aplicados.
 */
function controlSaveHandlerFilters() {
    filters = collectFilters();

    showFilters();
    hideModal("filter-courses-modal");

    initializeCoursesTable();
}

/**
 * Muestra los filtros aplicados en la interfaz de usuario.
 * Recorre el array de 'filters' y genera el HTML para cada filtro,
 * permitiendo su visualización y posterior eliminación. Además muestra u oculta
 * el botón de eliminación de filtros
 */
function showFilters() {
    // Eliminamos todos los filtros
    var currentFilters = document.querySelectorAll(".filter");

    // Recorre cada elemento y lo elimina
    currentFilters.forEach(function (filter) {
        filter.remove();
    });

    filters.forEach((filter) => {
        // Crea un nuevo div
        var newDiv = document.createElement("div");

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

    if (flatpickrInscriptionDate.selectedDates.length)
        addFilter(
            "Fecha de inscripción",
            getFlatpickrDateRangeSql(flatpickrInscriptionDate),
            getFlatpickrDateRange(flatpickrInscriptionDate),
            "filter_inscription_date",
            "inscription_date"
        );

    if (flatpickrRealizationDate.selectedDates.length)
        addFilter(
            "Fecha de realización",
            getFlatpickrDateRangeSql(flatpickrRealizationDate),
            getFlatpickrDateRange(flatpickrRealizationDate),
            "filter_realization_date",
            "realization_date"
        );

    let selectElementValidateStudents = document.getElementById(
        "filter_validate_student_registrations"
    );
    if (selectElementValidateStudents.value) {
        addFilter(
            "Validar registro de estudiantes",
            selectElementValidateStudents.value,
            selectElementValidateStudents.value == "1" ? "Sí" : "No",
            "filter_validate_student_registrations",
            "validate_student_registrations"
        );
    }

    const filter_min_ects_workload = document.getElementById(
        "filter_min_ects_workload"
    ).value;

    if (filter_min_ects_workload) {
        addFilter(
            "Mínimo ECTS",
            filter_min_ects_workload,
            filter_min_ects_workload,
            "filter_min_ects_workload",
            "min_ects_workload"
        );
    }

    const filter_max_ects_workload = document.getElementById(
        "filter_max_ects_workload"
    ).value;

    if (filter_max_ects_workload) {
        addFilter(
            "Máximo ECTS",
            filter_max_ects_workload,
            filter_max_ects_workload,
            "filter_max_ects_workload",
            "max_ects_workload"
        );
    }

    const filter_min_cost = document.getElementById("filter_min_cost").value;
    if (filter_min_cost)
        addFilter(
            "Coste mínimo",
            filter_min_cost,
            filter_min_cost,
            "filter_min_cost",
            "min_cost"
        );

    const filter_max_cost = document.getElementById("filter_max_cost").value;
    if (filter_max_cost)
        addFilter(
            "Coste máximo",
            filter_max_cost,
            filter_max_cost,
            "filter_max_cost",
            "max_cost"
        );

    // Collect values from TomSelects
    if (tomSelectCategoriesFilter) {
        const categories = tomSelectCategoriesFilter.getValue();

        const selectedCategoriesLabel = getOptionsSelectedTomSelectInstance(
            tomSelectCategoriesFilter
        );

        if (categories.length)
            addFilter(
                "Categorías",
                categories,
                selectedCategoriesLabel,
                "Categories",
                "categories"
            );
    }

    if (tomSelectCoordinatorsTeachersFilter) {
        const teachersCoordinators =
            tomSelectCoordinatorsTeachersFilter.getValue();

        const selectedCoordinatorsTeachersLabel =
            getOptionsSelectedTomSelectInstance(
                tomSelectCoordinatorsTeachersFilter
            );

        if (teachersCoordinators.length)
            addFilter(
                "Docentes coordinadores",
                tomSelectCoordinatorsTeachersFilter.getValue(),
                selectedCoordinatorsTeachersLabel,
                "coordinators_teachers",
                "coordinators_teachers"
            );
    }

    if (tomSelectNoCoordinatorsTeachersFilter) {
        const teachersNoCoordinators =
            tomSelectNoCoordinatorsTeachersFilter.getValue();

        const selectedNoCoordinatorsTeachersLabel =
            getOptionsSelectedTomSelectInstance(
                tomSelectNoCoordinatorsTeachersFilter
            );

        if (teachersNoCoordinators.length)
            addFilter(
                "Docentes no coordinadores",
                tomSelectNoCoordinatorsTeachersFilter.getValue(),
                selectedNoCoordinatorsTeachersLabel,
                "no_coordinators_teachers",
                "no_coordinators_teachers"
            );
    }

    if (tomSelectCreatorsFilter) {
        const creators = tomSelectCreatorsFilter.items;

        const selectedCreatorsLabel = getOptionsSelectedTomSelectInstance(
            tomSelectCreatorsFilter
        );

        if (creators.length) {
            addFilter(
                "Creadores",
                tomSelectCreatorsFilter.getValue(),
                selectedCreatorsLabel,
                "creators",
                "creator_user_uid"
            );
        }
    }

    if (tomSelectCourseStatusesFilter) {
        const courseStatuses = tomSelectCourseStatusesFilter.getValue();

        const selectedCourseStatusesLabel = getOptionsSelectedTomSelectInstance(
            tomSelectCourseStatusesFilter
        );

        if (courseStatuses.length) {
            addFilter(
                "Estados",
                tomSelectCourseStatusesFilter.getValue(),
                selectedCourseStatusesLabel,
                "course_statuses",
                "course_statuses"
            );
        }
    }

    if (tomSelectCallsFilter) {
        const calls = tomSelectCallsFilter.getValue();

        const selectedCallsLabel =
            getOptionsSelectedTomSelectInstance(tomSelectCallsFilter);

        if (calls.length) {
            addFilter(
                "Convocatorias",
                tomSelectCallsFilter.getValue(),
                selectedCallsLabel,
                "calls",
                "calls"
            );
        }
    }

    if (tomSelectEducationalProgramsFilter) {
        const educationalPrograms =
            tomSelectEducationalProgramsFilter.getValue();

        const selectedEducationalProgramsLabel =
            getOptionsSelectedTomSelectInstance(
                tomSelectEducationalProgramsFilter
            );

        if (educationalPrograms.length) {
            addFilter(
                "Programas educativos",
                tomSelectEducationalProgramsFilter.getValue(),
                selectedEducationalProgramsLabel,
                "educational_programs",
                "educational_programs"
            );
        }
    }

    if (tomSelectCourseTypesFilter) {
        const courseTypes = tomSelectCourseTypesFilter.getValue();

        const selectedCourseTypesLabel = getOptionsSelectedTomSelectInstance(
            tomSelectCourseTypesFilter
        );

        if (courseTypes.length) {
            addFilter(
                "Tipos de curso",
                tomSelectCourseTypesFilter.getValue(),
                selectedCourseTypesLabel,
                "course_types",
                "course_types"
            );
        }
    }

    const filter_min_required_students = document.getElementById(
        "filter_min_required_students"
    ).value;

    if (filter_min_required_students !== "") {
        addFilter(
            "Mínimo estudiantes requeridos",
            filter_min_required_students,
            filter_min_required_students,
            "filter_min_required_students",
            "min_required_students"
        );
    }

    const filter_max_required_students = document.getElementById(
        "filter_max_required_students"
    ).value;

    if (filter_max_required_students !== "") {
        addFilter(
            "Máximo estudiantes requeridos",
            filter_max_required_students,
            filter_max_required_students,
            "filter_max_required_students",
            "max_required_students"
        );
    }

    let selectElementCenter = document.getElementById("filter_center");
    if (selectElementCenter.value) {
        let selectedOptionText =
            selectElementCenter.options[selectElementCenter.selectedIndex].text;

        addFilter(
            "Centro",
            selectElementCenter.value,
            selectedOptionText,
            "filter_center",
            "center_uid"
        );
    }

    if (selectedCompetencesFilter.length) {
        addFilter(
            "Competencias seleccionadas",
            selectedCompetencesFilter,
            selectedCompetencesFilter.length,
            "filter_competences",
            "competences"
        );
    }

    return selectedFilters;
}

/**
 * Inicializa los controles 'TomSelect' para diferentes selecciones como
 * etiquetas, profesores, categorías, etc.
 * Configura varios aspectos como la creación de opciones y la eliminación.
 */
function initializeTomSelect() {
    tomSelectTags = getMultipleFreeTomSelectInstance("#tags");

    tomSelectContactEmails =
        getMultipleFreeEmailsTomSelectInstance("#contact_emails");

    tomSelectNoCoordinatorsTeachers = getMultipleTomSelectInstance(
        "#teachers-no-coordinators"
    );
    tomSelectCoordinatorsTeachers = getMultipleTomSelectInstance(
        "#teachers-coordinators"
    );

    tomSelectCategories = getMultipleTomSelectInstance("#select-categories");

    tomSelectCategoriesFilter =
        getMultipleTomSelectInstance("#filter_categories");

    tomSelectCoordinatorsTeachersFilter = getMultipleTomSelectInstance(
        "#filter_coordinators_teachers"
    );
    tomSelectNoCoordinatorsTeachersFilter = getMultipleTomSelectInstance(
        "#filter_no_coordinators_teachers"
    );

    tomSelectCourseStatusesFilter = getMultipleTomSelectInstance(
        "#filter_courses_statuses"
    );

    tomSelectCallsFilter = getMultipleTomSelectInstance("#filter_calls");

    tomSelectEducationalProgramsFilter = getMultipleTomSelectInstance(
        "#filter_educational_program_types"
    );

    tomSelectCourseTypesFilter = getMultipleTomSelectInstance(
        "#filter_course_types"
    );

    tomSelectCreatorsFilter = getLiveSearchTomSelectInstance(
        "#filter_creators",
        "/users/list_users/search_users_backend/",
        function (entry) {
            return {
                value: entry.uid,
                text: `${entry.first_name} ${entry.last_name}`,
            };
        }
    );
}

/**
 * Configura los eventos para el manejo del modal de cursos.
 * Incluye la lógica para mostrar/ocultar campos adicionales en el modal
 * y para resetear el modal al cerrarlo.
 */
function controlsHandlerModalCourse() {
    const selectValidateStudentsRegistration = document.getElementById(
        "validate-student-registrations"
    );

    const validationInformationDiv = document.getElementById(
        "validation-information-field"
    );

    if (selectValidateStudentsRegistration && validationInformationDiv) {
        selectValidateStudentsRegistration.addEventListener(
            "change",
            function () {
                if (this.value === "1") {
                    validationInformationDiv.classList.remove("hidden");
                    validationInformationDiv.classList.add("field");
                } else {
                    validationInformationDiv.classList.remove("field");
                    validationInformationDiv.classList.add("hidden");
                }
            }
        );
    }

    document
        .getElementById("add-course-btn")
        .addEventListener("click", function () {
            newCourse();
        });
}

/**
 * Inicializa la tabla de cursos utilizando 'Tabulator'.
 * Configura las columnas, las opciones de ajax y otros ajustes de la tabla.
 */
function initializeCoursesTable() {
    if (coursesTable) coursesTable.destroy();

    const columns = [
        {
            title: '<div class="checkbox-cell"><input type="checkbox" id="select-all-checkbox"/></div>',
            headerClick: function (e) {
                const selectAllCheckbox = e.target;
                if (selectAllCheckbox.type === "checkbox") {
                    // Asegúrate de que el clic fue en el checkbox
                    coursesTable.getRows().forEach((row) => {
                        const cell = row.getCell("select");
                        const checkbox = cell
                            .getElement()
                            .querySelector('input[type="checkbox"]');
                        checkbox.checked = selectAllCheckbox.checked;
                        updateSelectedCourses(checkbox, row.getData());
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
                const checkbox = e.target;
                const rowData = cell.getRow().getData();

                updateSelectedCourses(checkbox, rowData);
            },
            headerSort: false,
            width: 60,
        },
        { title: "Título", field: "title", resizable: true, widthgrow: 3 },
        {
            title: "Identificador",
            field: "identifier",
            visible: false,
            resizable: true,
            widthgrow: 2,
        },
        {
            title: "Estado",
            field: "status_name",
            formatter: function (cell, formatterParams, onRendered) {
                const color = getStatusCourseColor(
                    cell.getRow().getData().status_code
                );
                return `
                <div class="label-status" style="background-color:${color}">${
                    cell.getRow().getData().status_name
                }</div>
                `;
            },
            widthgrow: 2,
        },
        {
            title: "Fecha de inicio de realización",
            field: "realization_start_date",
            formatter: function (cell, formatterParams, onRendered) {
                const isoDate = cell.getValue();
                if (!isoDate) return "";
                return formatDateTime(isoDate);
            },
            widthgrow: 2,
        },
        {
            title: "Fecha de fin de realización",
            field: "realization_finish_date",
            formatter: function (cell, formatterParams, onRendered) {
                const isoDate = cell.getValue();
                if (!isoDate) return "";
                return formatDateTime(isoDate);
            },
            widthgrow: 2,
        },
        {
            title: "Convocatoria",
            field: "calls_name",
            formatter: function (cell, formatterParams, onRendered) {
                const data = cell.getValue();
                return data;
            },
            widthgrow: 2,
        },
        {
            title: "Programa educativo",
            field: "educational_programs_name",
            formatter: function (cell, formatterParams, onRendered) {
                const data = cell.getValue();
                return data;
            },
            widthgrow: 2,
            visible: false,
        },
        {
            title: "Tipo de programa educativo",
            field: "educational_program_types_name",
            formatter: function (cell, formatterParams, onRendered) {
                const data = cell.getValue();
                return data;
            },
            widthgrow: 2,
            visible: false,
        },
        {
            title: "Tipo de curso",
            field: "course_types_name",
            formatter: function (cell, formatterParams, onRendered) {
                const data = cell.getValue();
                return data;
            },
            widthgrow: 2,
            visible: false,
        },
        {
            title: "Mínimo de estudiantes requeridos",
            field: "min_required_students",
            formatter: function (cell, formatterParams, onRendered) {
                const data = cell.getValue();
                return data;
            },
            widthgrow: 2,
            visible: false,
        },
        {
            title: "Centro",
            field: "centers_name",
            formatter: function (cell, formatterParams, onRendered) {
                const data = cell.getValue();
                return data;
            },
            widthgrow: 2,
            visible: false,
        },
        {
            title: "Fecha de inicio de inscripción",
            field: "inscription_start_date",
            formatter: function (cell, formatterParams, onRendered) {
                const isoDate = cell.getValue();
                if (!isoDate) return "";
                return formatDateTime(isoDate);
            },
            widthgrow: 2,
            visible: false,
        },
        {
            title: "Fecha de fin de inscripción",
            field: "inscription_finish_date",
            formatter: function (cell, formatterParams, onRendered) {
                const isoDate = cell.getValue();
                if (!isoDate) return "";
                return formatDateTime(isoDate);
            },
            widthgrow: 2,
            visible: false,
        },
        {
            title: "Fecha de inicio de matriculación",
            field: "enrolling_start_date",
            formatter: function (cell, formatterParams, onRendered) {
                const isoDate = cell.getValue();
                if (!isoDate) return "";
                return formatDateTime(isoDate);
            },
            widthgrow: 2,
            visible: false,
        },
        {
            title: "Fecha de fin de matriculación",
            field: "enrolling_finish_date",
            formatter: function (cell, formatterParams, onRendered) {
                const isoDate = cell.getValue();
                if (!isoDate) return "";
                return formatDateTime(isoDate);
            },
            widthgrow: 2,
            visible: false,
        },
        {
            title: "Tipo de calificación",
            field: "calification_type",
            formatter: function (cell, formatterParams, onRendered) {
                const data = cell.getValue();
                if (data == "NUMERICAL") {
                    return "Numérica";
                } else {
                    return "Textual";
                }
            },
            widthgrow: 2,
            visible: false,
        },
        {
            title: "Video de presentación",
            field: "presentation_video_url",
            formatter: function (cell, formatterParams, onRendered) {
                const data = cell.getValue();
                if (data) {
                    return "<a href='" + data + "' target='_blank'>Enlace</a>";
                }
            },
            widthgrow: 2,
            visible: false,
        },
        {
            title: "Validar registros de estudiantes",
            field: "validate_student_registrations",
            formatter: function (cell, formatterParams, onRendered) {
                const data = cell.getValue();
                return data;
            },
            widthgrow: 2,
            visible: false,
        },
        {
            title: "Validar registros de estudiantes",
            field: "ects_workload",
            formatter: function (cell, formatterParams, onRendered) {
                const data = cell.getValue();
                return data;
            },
            widthgrow: 2,
            visible: false,
        },
        {
            title: "Etiquetas",
            field: "tags",
            formatter: function (cell, formatterParams, onRendered) {
                const data = cell.getValue();
                const tagsArray = [];
                data.forEach((item) => {
                    tagsArray.push(item.tag);
                });
                return tagsArray.join(", ");
            },
            widthgrow: 2,
            visible: false,
            headerSort: false,
        },
        {
            title: "Emails de contacto",
            field: "contact_emails",
            formatter: function (cell, formatterParams, onRendered) {
                const data = cell.getValue();
                const emailsArray = [];
                data.forEach((item) => {
                    emailsArray.push(item.email);
                });
                return emailsArray.join(", ");
            },
            widthgrow: 2,
            visible: false,
            headerSort: false,
        },
        {
            title: "URL de LSM",
            field: "lsm_url",
            formatter: function (cell, formatterParams, onRendered) {
                const data = cell.getValue();
                if (data) {
                    return "<a href='" + data + "' target='_blank'>Enlace</a>";
                }
            },
            widthgrow: 2,
            visible: false,
        },
        {
            title: "Docentes coordinadores",
            field: "teachers_coordinate",
            formatter: function (cell, formatterParams, onRendered) {
                const data = cell.getValue();
                const fullNames = data.map(
                    (professor) =>
                        `${professor.first_name} ${professor.last_name}`
                );
                const concatenatedNames = fullNames.join(", ");
                return concatenatedNames;
            },
            widthgrow: 2,
            visible: false,
            headerSort: false,
        },
        {
            title: "Docentes no coordinadores",
            field: "teachers_no_coordinate",
            formatter: function (cell, formatterParams, onRendered) {
                const data = cell.getValue();
                const fullNames = data.map(
                    (professor) =>
                        `${professor.first_name} ${professor.last_name}`
                );
                const concatenatedNames = fullNames.join(", ");
                return concatenatedNames;
            },
            widthgrow: 2,
            visible: false,
            headerSort: false,
        },
        {
            title: "Categorias",
            field: "categories",
            formatter: function (cell, formatterParams, onRendered) {
                const data = cell.getValue();
                const categoriesArray = [];
                data.forEach((item) => {
                    categoriesArray.push(item.name);
                });
                return categoriesArray.join(", ");
            },
            widthgrow: 2,
            visible: false,
            headerSort: false,
        },
        {
            title: "Coste",
            field: "cost",
            formatter: function (cell, formatterParams, onRendered) {
                const data = cell.getValue();
                return data + " €";
            },
            widthgrow: 2,
            visible: false,
        },
        {
            title: "Destacar en el carrousel grande",
            field: "featured_big_carousel",
            formatter: function (cell, formatterParams, onRendered) {
                const data = cell.getValue();
                if (data == 0) {
                    return "No";
                } else {
                    return "Si";
                }
            },
            widthgrow: 2,
            visible: false,
        },
        {
            title: "Destacar en el carrousel pequeño",
            field: "featured_small_carousel",
            formatter: function (cell, formatterParams, onRendered) {
                const data = cell.getValue();
                if (data == 0) {
                    return "No";
                } else {
                    return "Si";
                }
            },
            widthgrow: 2,
            visible: false,
        },
        {
            title: `<span class='cursor-pointer columns-selector'>${heroicon(
                "view-columns"
            )}</span>`,
            field: "actions",
            formatter: function (cell, formatterParams, onRendered) {
                return `<button type="button" class='btn action-btn'>${heroicon(
                    "pencil-square",
                    "outline"
                )}</button>`;
            },
            cellClick: function (e, cell) {
                e.preventDefault();

                const courseClicked = cell.getRow().getData();
                loadCourseModal(courseClicked.uid);
            },
            headerClick: function (e, column) {
                controlColumnsSecectorModal();
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
        {
            title: "",
            field: "actions",
            formatter: function (cell, formatterParams, onRendered) {
                return `
                    <button type="button" class='btn action-btn'>${heroicon(
                        "ellipsis-horizontal",
                        "outline"
                    )}</button>
                `;
            },
            cellClick: function (e, cell) {
                e.preventDefault();
                const btnArray = [
                    {
                        icon: "user-group",
                        type: "outline",
                        tooltip: "Listado de alumnos del curso",
                        action: (course) => {
                            loadCourseStudentsModal(course.uid);
                        },
                    },
                    {
                        icon: "document-duplicate",
                        type: "outline",
                        tooltip: "Duplicar curso",
                        action: (course) => {
                            showModalConfirmation(
                                "¿Deseas duplicar esta edición?",
                                "Se creará una nueva edición del curso con los mismos datos que la edición actual.",
                                "duplicateCourse",
                                [{ key: "course_uid", value: course.uid }]
                            ).then((result) => {
                                if (result) duplicateCourse(course.uid);
                            });
                        },
                    },
                    {
                        icon: "folder-plus",
                        type: "outline",
                        tooltip: "Crear nueva edición a partir de este curso",
                        action: (course) => {
                            showModalConfirmation(
                                "¿Deseas crear una nueva edición?",
                                "Se creará una nueva edición del curso con los mismos datos que la edición actual.",
                                "newEdition",
                                [{ key: "course_uid", value: course.uid }]
                            ).then((result) => {
                                if (result) newEditionCourse(course.uid);
                            });
                        },
                    },
                    {
                        icon: "academic-cap",
                        type: "outline",
                        tooltip: "Envío de credenciales",
                        action: (course) => {
                            showModalConfirmation(
                                "Envío de credenciales",
                                "¿Deseas enviar las credenciales a todos los usuarios que hayan finalizado?",
                                "sendCredentials",
                                [{ key: "course_uid", value: course.uid }]
                            );
                        },
                    },
                ];

                setTimeout(() => {
                    moreOptionsBtn(cell, btnArray);
                }, 1);
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
    ];

    const { ...tabulatorBaseConfigOverrided } = tabulatorBaseConfig;

    const actualTableConfiguration = getPaginationControls("courses-table");
    tabulatorBaseConfigOverrided.paginationSize =
        actualTableConfiguration.paginationSize;

    coursesTable = new Tabulator("#courses-table", {
        ...tabulatorBaseConfigOverrided,
        ajaxURL: endPointTable,
        rowContextMenu: [
            {
                label: `${heroicon("pencil-square")} Editar`,
                action: function (e, column) {
                    const courseClicked = column.getData();
                    loadCourseModal(courseClicked.uid);
                },
            },
            {
                label: `${heroicon("user-group")} Listado de alumnos`,
                action: function (e, column) {
                    const courseClicked = column.getData();
                    loadCourseStudentsModal(courseClicked.uid);
                },
            },
            {
                label: `${heroicon("document-duplicate")} Duplicar curso`,
                action: function (e, column) {
                    const courseClicked = column.getData();
                    showModalConfirmation(
                        "¿Deseas duplicar esta edición?",
                        "Se creará una nueva edición del curso con los mismos datos que la edición actual.",
                        "duplicateCourse"
                    ).then((result) => {
                        if (result) duplicateCourse(courseClicked.uid);
                    });
                },
            },
            {
                label: `${heroicon(
                    "folder-plus"
                )} Crear nueva edición a partir de este curso`,
                action: function (e, column) {
                    const courseClicked = column.getData();
                    showModalConfirmation(
                        "¿Deseas crear una nueva edición?",
                        "Se creará una nueva edición del curso con los mismos datos que la edición actual.",
                        "newEdition"
                    ).then((result) => {
                        if (result) newEditionCourse(courseClicked.uid);
                    });
                },
            },
            {
                label: `${heroicon("academic-cap")} Envío de credenciales`,
                action: function (e, column) {
                    showModalConfirmation(
                        "Envío de credenciales",
                        "¿Estás seguro de que quieres enviar las credenciales a los estudiantes seleccionados?"
                    ).then((result) => {});
                },
            },
        ],
        ajaxConfig: {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": getCsrfToken(),
            },
        },
        ajaxParams: {
            filters: {
                ...filters,
            },
        },
        ajaxResponse: function (url, params, response) {
            updatePaginationInfo(coursesTable, response, coursesTableId);

            document.getElementById("select-all-checkbox").checked = false;

            selectedCourses = [];

            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });

    controlsPagination(coursesTable, "courses-table");
    controlsSearch(coursesTable, endPointTable, "courses-table");
}

/**
 * Actualiza el array de cursos seleccionados cuando se marca o desmarca un curso.
 * Se utiliza principalmente para la selección de cursos en la tabla.
 */
function updateSelectedCourses(checkbox, rowData) {
    if (checkbox.checked) {
        if (!selectedCourses.includes(rowData.uid)) {
            selectedCourses.push({
                uid: rowData.uid,
                name: rowData.title,
                status: rowData.status_code,
            });
        }
    } else {
        const index = selectedCourses.findIndex(
            (course) => course.uid === rowData.uid
        );
        if (index > -1) {
            selectedCourses.splice(index, 1);
        }
    }
}

function duplicateCourse(courseUid) {
    const params = {
        url: "/learning_objects/courses/duplicate_course/" + courseUid,
        method: "POST",
        body: { course_uid: courseUid },
        toast: true,
        stringify: true,
    };

    apiFetch(params).then(() => {
        reloadTableCourses();
    });
}

function newEditionCourse(courseUid) {
    const params = {
        url: "/learning_objects/courses/create_edition",
        method: "POST",
        body: { course_uid: courseUid },
        toast: true,
        stringify: true,
    };

    apiFetch(params).then(() => {
        reloadTableCourses();
    });
}

function reloadTableCourses() {
    coursesTable.replaceData(endPointTable);
    const searchInput = document.querySelector(".search-table");
    searchInput.value = "";
}

/**
 * Carga los detalles de un curso en el modal de edición.
 * Realiza una petición 'fetch' para obtener los datos del curso y los carga en el formulario del modal.
 */
function loadCourseModal(courseUid) {
    const params = {
        url: "/learning_objects/courses/get_course/" + courseUid,
        method: "GET",
        loader: true,
    };

    apiFetch(params).then((data) => {
        fillFormCourseModal(data);
        showModal("course-modal", "Editar curso");
    });
}

/**
 * Rellena el formulario del modal de curso con los datos de un curso específico.
 * Configura los campos del formulario y los valores de los selectores TomSelect.
 */
function fillFormCourseModal(course) {
    resetModal();

    const draftButtonContainer = document.getElementById(
        "draft-button-container"
    );

    // Si el estado del curso no es INTRODUCCIÓN, ocultamos el botón de borrador
    if (course.status.code == "INTRODUCTION")
        draftButtonContainer.classList.remove("hidden");
    else draftButtonContainer.classList.add("hidden");

    document.getElementById("field-created-by").classList.remove("hidden");
    const createdByDiv = document.getElementById("created-by");

    if (course.creator_user) {
        createdByDiv.innerText =
            course.creator_user.first_name +
            " " +
            course.creator_user.last_name;
    } else {
        createdByDiv.innerText = "No disponible";
    }

    document.getElementById("course_uid").value = course.uid;

    fillFormWithObject(course, "course-form");

    if (course.inscription_start_date) {
        document.getElementById("inscription_start_date").value =
            course.inscription_start_date.substring(0, 16);
    }

    if (course.inscription_finish_date) {
        document.getElementById("inscription_finish_date").value =
            course.inscription_finish_date.substring(0, 16);
    }

    if (course.realization_start_date) {
        document.getElementById("realization_start_date").value =
            course.realization_start_date.substring(0, 16);
    }

    if (course.realization_finish_date) {
        document.getElementById("realization_finish_date").value =
            course.realization_finish_date.substring(0, 16);
    }

    if (course.call_uid) {
        document.getElementById("call_uid").value = course.call_uid;
        showCallField(true);
    } else {
        showCallField(operationByCalls);
    }

    if (
        course.validate_student_registrations ||
        (course.cost && course.cost > 0)
    ) {
        document
            .getElementById("enrolling-dates-container")
            .classList.remove("hidden");
    }

    if (course.enrolling_start_date) {
        document.getElementById("enrolling_start_date").value =
            course.enrolling_start_date.substring(0, 16);
    }

    if (course.enrolling_finish_date) {
        document.getElementById("enrolling_finish_date").value =
            course.enrolling_finish_date.substring(0, 16);
    }

    document.getElementById("belongs_to_educational_program").value =
        course.belongs_to_educational_program;

    document.getElementById("belongs_to_educational_program").checked =
        course.belongs_to_educational_program ? true : false;

    const validateStudentsRegistrations = course.validate_student_registrations
        ? true
        : false;

    document.getElementById("validate_student_registrations").value =
        course.validate_student_registrations;

    document.getElementById("validate_student_registrations").checked =
        validateStudentsRegistrations;

    const criteriaArea = document.getElementById("criteria-area");
    showArea(criteriaArea, validateStudentsRegistrations);

    document.getElementById("featured_big_carrousel").checked =
        course.featured_big_carrousel;
    document.getElementById("featured_big_carrousel").value =
        course.featured_big_carrousel;

    document.getElementById("featured_small_carrousel").value =
        course.featured_small_carrousel;
    document.getElementById("featured_small_carrousel").checked =
        course.featured_small_carrousel;

    const featuredSliderColorFont = document.getElementById(
        "featured_slider_color_font"
    );

    changeColorColoris(
        featuredSliderColorFont,
        course.featured_slider_color_font
    );

    showBigCarrouselInfo(course.featured_big_carrousel);

    if (course.teachers) {
        course.teachers.forEach((teacher) => {
            if (teacher.pivot.type == "NO_COORDINATOR") {
                tomSelectNoCoordinatorsTeachers.addOption({
                    value: teacher.uid,
                    text: teacher.name,
                });
                tomSelectNoCoordinatorsTeachers.addItem(teacher.uid);
            } else if (teacher.pivot.type == "COORDINATOR") {
                tomSelectCoordinatorsTeachers.addOption({
                    value: teacher.uid,
                    text: teacher.name,
                });
                tomSelectCoordinatorsTeachers.addItem(teacher.uid);
            }
        });
    }

    if (course.featured_big_carrousel_image_path) {
        document.getElementById(
            "featured_big_carrousel_image_path_preview"
        ).src = "/" + course.featured_big_carrousel_image_path;
    }

    if (course.categories) {
        course.categories.forEach((category) => {
            tomSelectCategories.addOption({
                value: category.uid,
                text: category.name,
            });
            tomSelectCategories.addItem(category.uid);
        });
    }

    if (course.tags) {
        course.tags.forEach((tag) => {
            tomSelectTags.addOption({ value: tag.tag, text: tag.tag });
            tomSelectTags.addItem(tag.tag);
        });
    }

    if (course.contact_emails) {
        course.contact_emails.forEach((contact_email) => {
            tomSelectContactEmails.addOption({
                value: contact_email.email,
                text: contact_email.email,
            });
            tomSelectContactEmails.addItem(contact_email.email);
        });
    }

    if (course.image_path) {
        document.getElementById("image_path_preview").src =
            "/" + course.image_path;
    } else {
        document.getElementById("image_path_preview").src = defaultImagePreview;
    }

    if (course.blocks) loadStructureCourse(course.blocks);

    loadDocuments(course.course_documents);
    updatePaymentMode(course.payment_mode);

    if (course.payment_mode === "INSTALLMENT_PAYMENT") {
        loadPaymentTerms(course.payment_terms);
    }

    const statusesAllowEdit = [
        "INTRODUCTION",
        "UNDER_CORRECTION_APPROVAL",
        "UNDER_CORRECTION_PUBLICATION",
    ];

    // Si es gestor, podrá tocar todos los campos
    if (window.rolesUser.includes("MANAGEMENT")) {
        toggleFormFieldsAccessibility(false);
    }
    // Establecemos los campos que se van a poder editar si es una edición
    else if (
        course.status.code === "INTRODUCTION" &&
        course.course_origin_uid
    ) {
        setFieldsNewEdition();
    } else if (
        statusesAllowEdit.includes(course.status.code) ||
        (course.educational_program &&
            statusesAllowEdit.includes(course.educational_program.status.code))
    ) {
        toggleFormFieldsAccessibility(false);
    } else {
        toggleFormFieldsAccessibility(true);
    }

    setVisibilityFieldsCourse(course);
}

function setVisibilityFieldsCourse(course) {
    if (course.belongs_to_educational_program) {
        setVisibilityCourseFieldsBasedOnProgramMembership(true);
    } else {
        setVisibilityCourseFieldsBasedOnProgramMembership(false);
        if (
            (course.cost && course.cost > 0) ||
            course.validate_student_registrations
        ) {
            document
                .getElementById("enrolling-dates-container")
                .classList.remove("hidden");
        }
    }
}

/**
 * Maneja el envío del formulario del curso.
 * Recoge los datos del formulario, incluyendo las etiquetas, profesores y categorías seleccionadas,
 * y realiza una petición 'fetch' para guardar o actualizar el curso.
 */
function submitFormCourseModal(event) {
    const action = event.submitter.value;

    const form = document.getElementById("course-form");

    const formData = new FormData(this);

    const tags = tomSelectTags.items;
    formData.append("tags", JSON.stringify(tags));

    const contactEmails = tomSelectContactEmails.items;
    formData.append("contact_emails", JSON.stringify(contactEmails));

    const teachersNoCoordinators = tomSelectNoCoordinatorsTeachers.items;
    formData.append(
        "teacher_no_coordinators",
        JSON.stringify(teachersNoCoordinators)
    );

    const teachersCoordinators = tomSelectCoordinatorsTeachers.items;
    formData.append(
        "teacher_coordinators",
        JSON.stringify(teachersCoordinators)
    );

    const categories = tomSelectCategories.items;
    formData.append("categories", JSON.stringify(categories));

    const checkboxFeaturedBigCarrousel = form.querySelector(
        "#featured_big_carrousel"
    );
    formData.append(
        "featured_big_carrousel",
        checkboxFeaturedBigCarrousel.checked ? "1" : "0"
    );

    const checkboxFeaturedSmallCarrousel = form.querySelector(
        "#featured_small_carrousel"
    );
    formData.append(
        "featured_small_carrousel",
        checkboxFeaturedSmallCarrousel.checked ? "1" : "0"
    );

    const checkboxValidateStudentRegistrations = form.querySelector(
        "#validate_student_registrations"
    );
    formData.append(
        "validate_student_registrations",
        checkboxValidateStudentRegistrations.checked ? "1" : "0"
    );

    const checkboxBelongsEducationalProgram = form.querySelector(
        "#belongs_to_educational_program"
    );
    formData.append(
        "belongs_to_educational_program",
        checkboxBelongsEducationalProgram.checked ? "1" : "0"
    );

    formData.append("structure", getStructureCourseJSON());
    formData.append("action", action);

    const featuredSliderColor = document.getElementById(
        "featured_slider_color_font"
    ).value;
    formData.append("featured_slider_color_font", featuredSliderColor);

    const documents = getDocuments();
    formData.append("documents", JSON.stringify(documents));

    const paymentTerms = getPaymentTerms();
    formData.append("payment_terms", JSON.stringify(paymentTerms));

    resetFormErrors("course-form");

    const params = {
        url: "/learning_objects/courses/save_course",
        method: "POST",
        body: formData,
        toast: true,
        loader: true,
    };

    apiFetch(params)
        .then(() => {
            reloadTableCourses();
            hideModal("course-modal");
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

/**
 * Configura el evento de clic para cambiar los estados de los cursos seleccionados.
 * Muestra un modal para que el usuario seleccione el nuevo estado y proporcione un motivo.
 */
function changeStatusesCourses() {
    if (!selectedCourses.length) {
        showToast("No has seleccionado ningún curso", "error");

        return;
    }

    let coursesList = document.getElementById("courses-list");
    coursesList.innerHTML = "";

    selectedCourses.forEach((course) => {
        const status = course.status;

        let optionsStatuses = [];

        if (
            [
                "PENDING_APPROVAL",
                "ACCEPTED",
                "REJECTED",
                "UNDER_CORRECTION_APPROVAL",
                "PENDING_PUBLICATION",
                "UNDER_CORRECTION_PUBLICATION",
                "ACCEPTED_PUBLICATION",
            ].includes(status)
        ) {
            optionsStatuses = [
                {
                    label: "Aceptado",
                    value: "ACCEPTED",
                },
                {
                    label: "Rechazado",
                    value: "REJECTED",
                },
                {
                    label: "En subsanación para aprobación",
                    value: "UNDER_CORRECTION_APPROVAL",
                },
                {
                    label: "Pendiente de publicación",
                    value: "PENDING_PUBLICATION",
                },
                {
                    label: "En subsanación para publicación",
                    value: "UNDER_CORRECTION_PUBLICATION",
                },
                {
                    label: "Aceptado para publicación",
                    value: "ACCEPTED_PUBLICATION",
                },
            ];

            // Excluímos el estado actual
            optionsStatuses = optionsStatuses.filter(
                (option) => option.value !== status
            );
        } else if (status === "PENDING_DECISION") {
            optionsStatuses = [
                {
                    label: "En inscripción",
                    value: "INSCRIPTION",
                },
            ];
        }

        // Se podrá retirar un curso en cualquier estado
        optionsStatuses.push({
            label: "Retirado",
            value: "RETIRED",
        });

        // Clonamos la plantilla de cambio de estado de curso
        let statusCourseTemplate = document
            .getElementById("change-status-course-template")
            .content.cloneNode(true);

        statusCourseTemplate.querySelector(
            ".change-status-course .course-name"
        ).innerText = course.name;
        statusCourseTemplate.querySelector(
            ".change-status-course .course"
        ).dataset.uid = course.uid;

        // Cargamos los estados
        let selectElement = statusCourseTemplate.querySelector(
            ".change-status-course .status-course"
        );
        optionsStatuses.forEach((option) => {
            let optionElement = document.createElement("option");
            optionElement.value = option.value;
            optionElement.text = option.label;

            selectElement.add(optionElement);
        });

        coursesList.appendChild(statusCourseTemplate);
    });

    document.getElementById("bulk_change_status").value = "";

    bulkChangeStatuses();
    showModal("change-statuses-courses-modal", "Cambiar estado de cursos");
}

/**
 * cambia el estado de todos los selectores en los cursos en el modal cambio de estado
 */
function bulkChangeStatuses() {
    const bulkSelect = document.getElementById("bulk_change_status");
    const selectors = document.querySelectorAll("#courses-list .status-course");
    bulkSelect.addEventListener("change", function () {
        selectors.forEach((select) => {
            var opcionExist = select.querySelector(
                'option[value="' + bulkSelect.value + '"]'
            );
            if (opcionExist) {
                opcionExist.selected = true;
            }
        });
    });
}

/**
 * Envía los cambios de estado de los cursos seleccionados.
 * Recoge los nuevos estados y los motivos de cambio de cada curso y realiza una petición 'fetch' para actualizarlos.
 */
function submitChangeStatusesCourses() {
    const changesCoursesStatuses = getCoursesStatuses();

    const params = {
        url: "/learning_objects/courses/change_statuses_courses",
        method: "POST",
        body: { changesCoursesStatuses },
        toast: true,
        loader: true,
        stringify: true,
    };

    apiFetch(params)
        .then(() => {
            hideModal("change-statuses-courses-modal");
            reloadTableCourses();
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

function getCoursesStatuses() {
    const courseContainer = document.getElementById("courses-list");
    const courseDivs = courseContainer.querySelectorAll("div.course");
    const changesCoursesStatuses = [];

    courseDivs.forEach((courseElement) => {
        const uid = courseElement.getAttribute("data-uid");
        const statusElement = courseElement.querySelector(".status-course");
        const reasonElement = courseElement.querySelector(
            ".reason-status-course"
        );

        const status = statusElement.value;
        const reason = reasonElement.value;

        changesCoursesStatuses.push({
            uid,
            status,
            reason,
        });
    });

    return changesCoursesStatuses;
}

function showCallField(show) {
    const callField = document.getElementById("call-field");

    if (show) callField.classList.remove("hidden");
    else callField.classList.add("hidden");
}

/**
 * Prepara y muestra el modal para añadir un nuevo curso.
 * Resetea el formulario del modal y muestra el modal en blanco para la entrada de datos.
 */
function newCourse() {
    const draftButtonContainer = document.getElementById(
        "draft-button-container"
    );
    draftButtonContainer.classList.remove("hidden");

    showCallField(operationByCalls);

    resetModal();
    document.getElementById("course-composition").innerHTML = "";
    document.getElementById("document-list").innerHTML = "";
    toggleFormFieldsAccessibility(false);
    updatePaymentMode("SINGLE_PAYMENT");
    showModal("course-modal", "Añadir curso");
}

/**
 * Resetea el formulario y otros elementos del modal de curso.
 * Limpia los campos del formulario, restablece los selectores TomSelect y elimina los errores mostrados.
 * Deja visible todos los campos por defecto y oculta los campos específicos de la creación de un nuevo curso.
 */
function resetModal() {
    const form = document.getElementById("course-form");
    form.reset();
    // Reseteo del campo uid ya que al ser hidden, no le afecta form.reset()
    // Reseteamos solo este campo y no todos los hidden porque si no nos cargamos el campo del token csrf
    document.getElementById("course_uid").value = "";

    // Reseteo de previsualicion de imagen
    document.getElementById("field-created-by").classList.add("hidden");
    document.getElementById("image_path_preview").src = defaultImagePreview;
    document.getElementById("featured_big_carrousel_image_path_preview").src =
        defaultImagePreview;
    document.getElementById("course-composition").innerHTML = "";
    document.getElementById("documents-container").classList.add("hidden");
    document
        .getElementById("enrolling-dates-container")
        .classList.add("hidden");

    tomSelectNoCoordinatorsTeachers.clear();
    tomSelectCoordinatorsTeachers.clear();

    tomSelectCategories.clear();
    tomSelectTags.clear();
    tomSelectContactEmails.clear();

    resetFormErrors("course-form");
    showBigCarrouselInfo(false);

    let criteriaArea = document.getElementById("criteria-area");
    showArea(criteriaArea, false);

    let enrollingArea = document.getElementById("enrolling-dates-container");
    showArea(enrollingArea, false);

    let validateStudentRegistration = document.getElementById(
        "validate-student-registrations-container"
    );
    showArea(validateStudentRegistration, true);

    let inscriptionDatesContainer = document.getElementById(
        "inscription-dates-container"
    );
    showArea(inscriptionDatesContainer, true);

    let minStudentsRequiredContainer = document.getElementById(
        "min-required-students-container"
    );
    showArea(minStudentsRequiredContainer, true);

    let costContainer = document.getElementById("cost-container");
    showArea(costContainer, true);

    let tagsContainer = document.getElementById("tags-container");
    showArea(tagsContainer, true);

    let categoriesContainer = document.getElementById("categories-container");
    showArea(categoriesContainer, true);

    let featureMainSliderContainer = document.getElementById(
        "feature-main-slider-container"
    );
    showArea(featureMainSliderContainer, true);

    let featureMainCarrouselContainer = document.getElementById(
        "feature-main-carrousel-container"
    );
    showArea(featureMainCarrouselContainer, true);

    document.getElementById("payment-terms-list").innerHTML = "";
}

/**
 * Carga y muestra el modal para la gestión de estudiantes de un curso.
 * Inicializa la tabla de estudiantes del curso y configura los eventos para la búsqueda y eliminación de estudiantes.
 */
function loadCourseStudentsModal(courseUid) {
    if (courseStudensTable != null && courseStudensTable != undefined) {
        courseStudensTable.destroy();
    }

    selectedCourseUid = courseUid;

    initializeCourseStudentsTable(courseUid);

    controlsPagination(courseStudensTable, "course-students-table");

    showModal("course-students-modal", "Listado de alumnos");
}

/**
 * Inicializa la tabla de estudiantes de un curso específico.
 * Configura las columnas y las opciones de ajax para la tabla de estudiantes usando 'Tabulator'.
 */
function initializeCourseStudentsTable(courseUid) {
    const columns = [
        {
            title: '<div class="checkbox-cell"><input type="checkbox" id="select-all-checkbox"/></div>',
            field: "select",
            formatter: function (cell, formatterParams, onRendered) {
                const uid = cell.getRow().getData().uid;
                return `<input type="checkbox" data-uid="${uid}"/>`;
            },
            cssClass: "checkbox-cell",
            headerClick: function (e) {
                selectedCourseStudents = handleHeaderClick(
                    courseStudensTable,
                    e,
                    selectedCourseStudents
                );
            },
            cellClick: function (e, cell) {
                // Lógica cuando se hace clic en la celda
                const checkbox = e.target;
                const data = cell.getRow().getData();

                selectedCourseStudents = updateArrayRecords(
                    checkbox,
                    data,
                    selectedCourseStudents
                );
            },
            headerSort: false,
            width: 60,
        },
        { title: "Nombre", field: "first_name", widthGrow: "2" },
        { title: "Apellidos", field: "last_name", widthGrow: "3" },
        { title: "NIF", field: "nif", widthGrow: "2" },
        {
            title: "Aceptado",
            field: "courses_students.acceptance_status",
            formatter: function (cell, formatterParams, onRendered) {
                const rowData = cell.getRow().getData();
                const acceptanceStatus =
                    rowData.course_student_info.acceptance_status;
                if (acceptanceStatus === "PENDING") return "Pendiente";
                else if (acceptanceStatus === "ACCEPTED") return "Sí";
                else if (acceptanceStatus === "REJECTED") return "No";
            },
            widthGrow: "3",
            resizable: false,
        },
        {
            title: "Documentos presentados",
            field: "actions",
            formatter: function (cell, formatterParams, onRendered) {
                const rowData = cell.getRow().getData();
                if (rowData.course_student_documents.length) {
                    return `
                    <button class="dropdown-button" type="button" id="menu-button" aria-expanded="true" aria-haspopup="true">
                        Descarga
                        ${heroicon("chevron-down", "outline")}
                    </button>
                </div>
                    `;
                } else {
                    return "-";
                }
            },
            cellClick: function (e, cell) {
                e.preventDefault();
                const rowData = cell.getRow().getData();

                if (rowData.course_student_documents) {
                    const btnArray = [];
                    rowData.course_student_documents.forEach((document) => {
                        btnArray.push({
                            text: document.course_document.document_name,
                            action: () => {
                                downloadDocument(document.uid);
                            },
                        });
                    });

                    setTimeout(() => {
                        dropdownMenu(cell, btnArray);
                    }, 1);
                }
            },
            cssClass: "text-center",
            headerSort: false,
            widthGrow: 3,
            resizable: false,
        },
    ];

    courseStudensTable = new Tabulator("#course-students-table", {
        ajaxURL: `${endPointStudentTable}/${courseUid}`,
        ajaxConfig: "GET",
        ...tabulatorBaseConfig,
        ajaxResponse: function (url, params, response) {
            updatePaginationInfo(
                courseStudensTable,
                response,
                "course-students-table"
            );
            selectedCourseStudents = [];
            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });

    controlsPagination(courseStudensTable, "course-students-table");

    controlsSearch(
        courseStudensTable,
        `${endPointStudentTable}/${courseUid}`,
        "course-students-table"
    );
}

function downloadDocument(uidDocument) {
    const params = {
        url: "/learning_objects/courses/download_document_student",
        method: "POST",
        body: { uidDocument: uidDocument },
        toast: false,
        loader: true,
        stringify: true,
        download: true,
    };

    apiFetch(params);
}

function controlChecksCarrousels() {
    const checkbox = document.getElementById("featured_big_carrousel");

    checkbox.addEventListener("change", function () {
        if (checkbox.checked) showBigCarrouselInfo(true);
        else showBigCarrouselInfo(false);
    });
}

function showBigCarrouselInfo(show) {
    const bigCarrouselInfoSection =
        document.getElementById("big-carrousel-info");

    if (show) {
        bigCarrouselInfoSection.classList.remove("hidden");
        bigCarrouselInfoSection.classList.add("block");
    } else {
        bigCarrouselInfoSection.classList.remove("block");
        bigCarrouselInfoSection.classList.add("hidden");
    }
}

function approveStudentsCourse() {
    const uidsStudentsInscriptions = getUidsStudentsInscriptions();

    const params = {
        url: "/learning_objects/courses/approve_inscriptions_course",
        method: "POST",
        body: { uids: uidsStudentsInscriptions },
        toast: true,
        loader: true,
        stringify: true,
    };

    apiFetch(params)
        .then(() => {
            hideModal("change-statuses-courses-modal");
            reloadStudentsTable();
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

function rejectStudentsCourse() {
    const uidsStudentsInscriptions = getUidsStudentsInscriptions();

    const params = {
        url: "/learning_objects/courses/reject_inscriptions_course",
        method: "POST",
        body: { uids: uidsStudentsInscriptions },
        toast: true,
        loader: true,
        stringify: true,
    };

    apiFetch(params)
        .then(() => {
            hideModal("change-statuses-courses-modal");
            reloadStudentsTable();
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

function reloadStudentsTable() {
    const endpoint = `${endPointStudentTable}/${selectedCourseUid}`;
    courseStudensTable.replaceData(endpoint);
}

function getUidsStudentsInscriptions() {
    return selectedCourseStudents.map((student) => {
        return student.course_student_info.uid;
    });
}
/**
 *
 * @param {*} statusCode
 * @returns Color de fondo que le coresponde a la etiqueta del estado
 */
function getStatusCourseColor(statusCode) {
    const statusColors = {
        INTRODUCTION: "#EBEBF4",
        PENDING_APPROVAL: "#F0F4EB",
        ACCEPTED: "#EBF3F4",
        REJECTED: "#F4EBF0",
        UNDER_CORRECTION_APPROVAL: "#F0F4EB",
        PENDING_PUBLICATION: "#F4EFEB",
        ACCEPTED_PUBLICATION: "#F4F3EB",
        UNDER_CORRECTION_PUBLICATION: "#F4EBEB",
        INSCRIPTION: "#EBEFF4",
        PENDING_INSCRIPTION: "#FBEDED",
        DEVELOPMENT: "#EDF4FB",
        FINISHED: "#FBF4ED",
        RETIRED: "#FDF5FE",
        ENROLLING: "#F3F4FF",
        READY_ADD_EDUCATIONAL_PROGRAM: "#F3FBED",
        ADDED_EDUCATIONAL_PROGRAM: "#EDFBF6",
    };

    return statusColors[statusCode];
}

/**
 * Muestra u oculta el área de criterios de evaluación y el selector de documentos
 * en función de si el curso tiene validación de estudiantes.
 */
function controlValidationStudents() {
    var checkbox = document.getElementById("validate_student_registrations");

    const documentsContainer = document.getElementById("documents-container");
    const criteriaArea = document.getElementById("criteria-area");

    checkbox.addEventListener("change", function () {
        if (checkbox.checked) {
            showArea(documentsContainer, true);
            showArea(criteriaArea, true);
        } else {
            showArea(documentsContainer, false);
            showArea(criteriaArea, false);
        }
    });
}

/**
 * Muestra u oculta el plazo de matriculación si el curso tiene coste o si tiene validación de
 * estudiantes
 */
function controlEnrollingDates() {
    const costInput = document.getElementById("cost");
    const checkboxValidateStudents = document.getElementById(
        "validate_student_registrations"
    );
    const enrollingDates = document.getElementById("enrolling-dates-container");
    const paymentMode = document.getElementById("payment_mode");

    function checkConditions() {
        const shouldShow =
            (costInput.value > 0 && paymentMode.value === "SINGLE_PAYMENT") ||
            checkboxValidateStudents.checked;
        showArea(enrollingDates, shouldShow);
    }

    costInput.addEventListener("change", checkConditions);
    checkboxValidateStudents.addEventListener("change", checkConditions);
    paymentMode.addEventListener("change", checkConditions);
}

function loadStructureCourse(blocks) {
    // Obtiene las plantillas de los bloques y elementos del DOM
    const blockTemplate = document.getElementById("block-template");
    const subBlockTemplate = document.getElementById("sub-block-template");
    const elementTemplate = document.getElementById("element-template");
    const subElementTemplate = document.getElementById("sub-element-template");

    // Recorre cada bloque en la estructura del curso
    blocks.forEach(async (block) => {
        // Clona la plantilla del bloque
        const blockHtml = blockTemplate.content.cloneNode(true);

        // Asigna los valores del bloque a los campos correspondientes en el HTML
        blockHtml.querySelector(".block-type").value = block.type;
        blockHtml.querySelector(".block-name").value = block.name;
        blockHtml.querySelector(".block-description").value = block.description;
        blockHtml.querySelector(".search-tree").dataset.order = block.order;
        let blockHtmlElement = blockHtml.querySelector(".block");
        blockHtmlElement.dataset.uid = block.uid;
        blockHtmlElement.dataset.order = block.order;

        // Añade competencias
        blockHtmlElement.querySelector(".competences-section").dataset.order =
            block.order;

        // Recorre cada sub-bloque en el bloque
        if (block.sub_blocks) {
            block.sub_blocks.forEach((subBlock) => {
                // Clona la plantilla del sub-bloque
                const subBlockHtml = subBlockTemplate.content.cloneNode(true);
                let subBlockHtmlElement =
                    subBlockHtml.querySelector(".sub-block");

                // Asigna los valores del sub-bloque a los campos correspondientes en el HTML
                subBlockHtml.querySelector(".sub-block-name").value =
                    subBlock.name;
                subBlockHtml.querySelector(".sub-block-description").value =
                    subBlock.description;
                subBlockHtmlElement.dataset.uid = subBlock.uid;
                subBlockHtmlElement.dataset.order = subBlock.order;

                // Recorre cada elemento en el sub-bloque
                if (subBlock.elements) {
                    subBlock.elements.forEach((element) => {
                        // Clona la plantilla del elemento
                        const elementHtml =
                            elementTemplate.content.cloneNode(true);

                        // Asigna los valores del elemento a los campos correspondientes en el HTML
                        elementHtml.querySelector(".element-name").value =
                            element.name;
                        elementHtml.querySelector(
                            ".element-description"
                        ).value = element.description;

                        let elementHtmlElement =
                            elementHtml.querySelector(".element");
                        elementHtmlElement.dataset.order = element.order;
                        elementHtmlElement.dataset.uid = element.uid;

                        // Recorre cada sub-elemento en el elemento
                        if (element.sub_elements) {
                            element.sub_elements.forEach((subElement) => {
                                // Clona la plantilla del sub-elemento
                                const subElementHtml =
                                    subElementTemplate.content.cloneNode(true);

                                // Asigna los valores del sub-elemento a los campos correspondientes en el HTML
                                subElementHtml.querySelector(
                                    ".sub-element-name"
                                ).value = subElement.name;
                                subElementHtml.querySelector(
                                    ".sub-element-description"
                                ).value = subElement.description;

                                let subElementHtmlElement =
                                    subElementHtml.querySelector(
                                        ".sub-element"
                                    );
                                subElementHtmlElement.dataset.order =
                                    subElement.order;

                                subElementHtmlElement.dataset.uid =
                                    subElement.uid;

                                // Añade el sub-elemento al elemento en el HTML
                                elementHtml
                                    .querySelector(".sub-elements")
                                    .appendChild(subElementHtml);
                            });
                        }

                        // Añade el elemento al sub-bloque en el HTML
                        subBlockHtml
                            .querySelector(".elements")
                            .appendChild(elementHtml);
                    });
                }

                // Añade el sub-bloque al bloque en el HTML
                blockHtml
                    .querySelector(".sub-blocks")
                    .appendChild(subBlockHtml);
            });
        }

        // Añade el bloque al curso en el HTML
        document.getElementById("course-composition").appendChild(blockHtml);

        const uidsLearningResults = block.learning_results.map(
            (learningResult) => learningResult.uid
        );

        let tree = await instanceTreeCompetences(
            block.order,
            uidsLearningResults
        );

        Trees.storeInstance(block.order, tree, uidsLearningResults);
    });
}

/**
 * Activa o desactiva campos del formulario
 * @param {*} formId
 * @param {*} isDisabled
 */
function toggleFormFieldsAccessibility(isDisabled) {
    const formId = "course-form";
    const idsReadOnly = [
        "title",
        "description",
        "contact_information",
        "min_required_students",
        "center",
        "objectives",
        "ects_workload",
        "evaluation_criteria",
        "inscription_start_date",
        "inscription_finish_date",
        "realization_start_date",
        "realization_finish_date",
        "enrolling_start_date",
        "enrolling_finish_date",
        "cost",
        "presentation_video_url",
        "lms_url",
        "image_input_file",
        "featured_small_carrousel",
        "generate-tags-btn",
    ];
    setReadOnlyForSpecificFields(formId, idsReadOnly, isDisabled);

    const idsDisable = [
        "validate_student_registrations",
        "call_uid",
        "educational_program_type_uid",
        "course_type_uid",
        "center_uid",
        "belongs_to_educational_program",
        "image_input_file",
        "calification_type",
        "lms_system_uid",
        "featured_small_carrousel",
        "payment_mode",
    ];
    setDisabledSpecificFormFields(formId, idsDisable, isDisabled);

    // Desactivamos el div de composición del curso
    const idsDivsBlock = [
        "course-composition-block",
        "document-container",
        "payment_terms",
    ];

    setDisabledSpecificDivFields(idsDivsBlock, isDisabled);

    // Habilitamos la botonera y el selector de carrousel grande
    const idsDivsAllow = ["feature-main-slider-container", "btns-save"];
    setDisabledSpecificDivFields(idsDivsAllow, isDisabled);

    if (isDisabled) {
        tomSelectNoCoordinatorsTeachers.disable();
        tomSelectCoordinatorsTeachers.disable();

        tomSelectCategories.disable();
        tomSelectTags.disable();
        tomSelectContactEmails.disable();
    } else {
        tomSelectNoCoordinatorsTeachers.enable();
        tomSelectCoordinatorsTeachers.enable();

        tomSelectCategories.enable();
        tomSelectTags.enable();
        tomSelectContactEmails.enable();
    }
}

/**
 * Prepara los campos del formulario para una nueva edición.
 */
function setFieldsNewEdition() {
    // Ponemos readonly estos campos
    const idsReadOnly = [
        "title",
        "description",
        "contact_information",
        "min_required_students",
        "center",
        "objectives",
        "ects_workload",
    ];
    setReadOnlyForSpecificFields("course-form", idsReadOnly, true);

    // Desbloqueamos estos campos que son los que permitiremos modificar
    const idsUnblock = [
        "inscription_start_date",
        "inscription_finish_date",
        "realization_start_date",
        "realization_finish_date",
        "cost",
        "presentation_video_url",
        "lms_url",
        "image_input_file",
        "featured_small_carrousel",
    ];
    setReadOnlyForSpecificFields("course-form", idsUnblock, false);

    const idsEnable = ["lms_system_uid"];
    setDisabledSpecificFormFields("course-form", idsEnable, false);

    // Desactivamos estos campos
    const idsDisable = [
        "educational_program_type_uid",
        "course_type_uid",
        "belongs_to_educational_program",
        "calification_type",
        "center_uid",
    ];
    setDisabledSpecificFormFields("course-form", idsDisable, true);

    // Desactivamos los selectores
    tomSelectTags.disable();
    tomSelectCategories.disable();
    tomSelectNoCoordinatorsTeachers.disable();
    tomSelectCoordinatorsTeachers.disable();

    tomSelectContactEmails.disable();

    // Desactivamos el div de composición del curso
    const idsDivsBlock = ["course-composition-block"];

    setDisabledSpecificDivFields(idsDivsBlock, true);

    // Habilitamos la botonera y el selector de carrousel grande
    const idsDivsAllow = ["feature-main-slider-container", "btns-save"];
    setDisabledSpecificDivFields(idsDivsAllow, false);
}

function getDocuments() {
    const courseDocuments = document
        .getElementById("document-list")
        .querySelectorAll(".document");
    const documentsData = [];

    courseDocuments.forEach((courseDocument) => {
        const uid = courseDocument.dataset.documentUid;
        const document_name =
            courseDocument.querySelector(".document-name").value;
        documentsData.push({ uid, document_name });
    });

    return documentsData;
}

function getPaymentTerms() {
    const coursePaymentTerms = document
        .getElementById("payment-terms-list")
        .querySelectorAll(".payment-term");

    const paymentTermsData = [];

    coursePaymentTerms.forEach((coursePaymentTerm) => {
        let paymentTermData = {
            uid: coursePaymentTerm.dataset.paymentTermUid ?? null,
            name: coursePaymentTerm.querySelector(".payment-term-name").value,
            start_date: coursePaymentTerm.querySelector(
                ".payment-term-start-date"
            ).value,
            finish_date: coursePaymentTerm.querySelector(
                ".payment-term-finish-date"
            ).value,
            cost: coursePaymentTerm.querySelector(".payment-term-cost").value,
        };

        paymentTermsData.push(paymentTermData);
    });

    return paymentTermsData;
}

function loadDocuments(courseDocuments) {
    // Limpiar el contenedor de documentos
    const containerDocuments = document.getElementById("document-list");
    containerDocuments.innerHTML = "";

    // Añadir cada documento al contenedor
    courseDocuments.forEach((courseDocument) => {
        const documentTemplate = document
            .getElementById("document-template")
            .content.cloneNode(true);

        documentTemplate.querySelector(".document").dataset.documentUid =
            courseDocument.uid;
        documentTemplate.querySelector(".document-name").value =
            courseDocument.document_name;
        containerDocuments.appendChild(documentTemplate);
    });
}

function loadPaymentTerms(paymentTerms) {
    // Limpiar el contenedor de términos de pago
    const containerPaymentTerms = document.getElementById("payment-terms-list");
    containerPaymentTerms.innerHTML = "";

    // Añadir cada término de pago al contenedor
    paymentTerms.forEach((paymentTerm) => {
        const paymentTermTemplate = document
            .getElementById("payment-term-template")
            .content.cloneNode(true);

        paymentTermTemplate.querySelector(
            ".payment-term"
        ).dataset.paymentTermUid = paymentTerm.uid;
        paymentTermTemplate.querySelector(
            ".payment-term"
        ).dataset.paymentTermUid = paymentTerm.uid;
        paymentTermTemplate.querySelector(".payment-term-name").value =
            paymentTerm.name;
        paymentTermTemplate.querySelector(".payment-term-start-date").value =
            paymentTerm.start_date;
        paymentTermTemplate.querySelector(".payment-term-finish-date").value =
            paymentTerm.finish_date;
        paymentTermTemplate.querySelector(".payment-term-cost").value =
            paymentTerm.cost;
        containerPaymentTerms.appendChild(paymentTermTemplate);
    });
}

// Controla que no se puedan seleccionar un docente como coordinador y no coordinador a la vez
function syncTomSelectsTeachers() {
    tomSelectCoordinatorsTeachers.on("item_add", function (value) {
        if (tomSelectNoCoordinatorsTeachers.getValue().includes(value)) {
            tomSelectNoCoordinatorsTeachers.removeItem(value);
        }
    });

    tomSelectNoCoordinatorsTeachers.on("item_add", function (value) {
        if (tomSelectCoordinatorsTeachers.getValue().includes(value)) {
            tomSelectCoordinatorsTeachers.removeItem(value);
        }
    });
}

//abrimos modal para seleccionar columnas
function controlColumnsSecectorModal() {
    showModal("columns-courses-modal");
    var checkboxes = document.querySelectorAll(
        '.checkbox_columns_selector input[type="checkbox"]'
    );
    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener("click", function () {
            if (this.checked) {
                coursesTable.showColumn(this.value);
                coursesTable.redraw();
            } else {
                coursesTable.hideColumn(this.value);
                coursesTable.redraw();
            }
        });
    });
}

function enrollStudentsToCourse() {
    const usersToEnroll = tomSelectUsersToEnroll.getValue();

    const formData = new FormData();

    formData.append("courseUid", selectedCourseUid);
    usersToEnroll.forEach((user) => {
        formData.append("usersToEnroll[]", user);
    });

    const params = {
        url: "/learning_objects/courses/enroll_students",
        method: "POST",
        body: formData,
        loader: true,
        toast: true,
    };

    apiFetch(params)
        .then(() => {
            hideModal("enroll-course-modal");
            tomSelectUsersToEnroll.destroy();
            tomSelectUsersToEnroll = getLiveSearchTomSelectInstance(
                "#enroll_students",
                "/users/list_users/search_users_no_enrolled/" +
                    selectedCourseUid +
                    "/",
                function (entry) {
                    return {
                        value: entry.uid,
                        text: `${entry.first_name} ${entry.last_name}`,
                    };
                }
            );
            reloadStudentsTable();
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

function enrollStudentsCsv() {
    const fileInput = document.getElementById("attachment");
    const file = fileInput.files[0];

    const formData = new FormData();
    formData.append("attachment", file);
    formData.append("course_uid", selectedCourseUid);

    const params = {
        url: "/learning_objects/courses/enroll_students_csv",
        method: "POST",
        body: formData,
        toast: true,
        loader: true,
    };

    apiFetch(params)
        .then(() => {
            hideModal("enroll-course-csv-modal");
            reloadStudentsTable();
            fileInput.value = "";
        })
        .catch((data) => {
            showFormErrors(data.errors);
            fileInput.value = "";
        });
}

/**
 * Función que se encarga de configurar los campos del curso en función de la pertenencia a un programa
 * @param {} isPartOfProgram
 */
function setVisibilityCourseFieldsBasedOnProgramMembership(isPartOfProgram) {
    const elementIds = [
        "min-required-students-container",
        "inscription-dates-container",
        "validate-student-registrations-container",
        "tags-container",
        "categories-container",
        "cost-container",
        "feature-main-slider-container",
        "feature-main-carrousel-container",
    ];

    elementIds.forEach((id) => {
        const element = document.getElementById(id);
        showArea(element, !isPartOfProgram);
    });

    if (isPartOfProgram) {
        document.getElementById("featured_big_carrousel").checked = false;
        document.getElementById("featured_big_carrousel").value = 0;
        showBigCarrouselInfo(false);

        document.getElementById("featured_small_carrousel").checked = false;
        document.getElementById("featured_small_carrousel").value = 0;
    }
}

function showArea(area, show) {
    if (show) area.classList.remove("hidden");
    else area.classList.add("hidden");
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

function updatePaymentMode(paymentMode) {
    if (paymentMode == "SINGLE_PAYMENT") {
        document.getElementById("cost").classList.remove("hidden");
        document.getElementById("payment_terms").classList.add("hidden");
        document
            .getElementById("label-container-cost")
            .classList.add("label-center");
    } else {
        document.getElementById("cost").classList.add("hidden");
        document
            .getElementById("label-container-cost")
            .classList.remove("label-center");

        // Si no hay términos de pago, añadimos uno por defecto
        if (!document.querySelector(".payment-term")) {
            addPaymentTerm();
        }

        document.getElementById("payment_terms").classList.remove("hidden");
    }
}

function addPaymentTerm() {
    const template = document
        .getElementById("payment-term-template")
        .content.cloneNode(true);
    document.getElementById("payment-terms-list").appendChild(template);
}

function removePaymentTerm(event) {
    let target = event.target;

    if (!target.classList.contains(".btn-remove-payment-term")) {
        target = target.closest(".btn-remove-payment-term");
    }

    if (target) {
        target.closest(".payment-term").remove();
    }
}
