import {
    hideModal,
    showModal,
    showModalConfirmation,
} from "../modal_handler.js";
import {
    showFormErrors,
    resetFormErrors,
    updateInputImage,
    apiFetch,
    fillFormWithObject,
    resetFormFields,
    updateInputFile,
} from "../app.js";
import { showToast } from "../toast.js";
import InfiniteTree from "infinite-tree";
import renderer from "../renderer_infinite_tree.js";
import { heroicon } from "../heroicons.js";

let competencesLearningResults = [];
let treeCompetencesLearningResults;
let selectedCompetencesLearningResults;

document.addEventListener("DOMContentLoaded", async function () {
    initHandlers();
    instanceSelectedCompetencesLearningResults();
    updateInputFile();
    await loadTreeCompetencesLearningResults();
    instanceTreeCompetences();
});

async function initHandlers() {
    document
        .getElementById("search-competences-btn")
        .addEventListener("click", function (e) {
            const textToSearch = document.getElementById(
                "search-competences-input"
            ).value;
            filterTree(textToSearch);
        });

    document
        .getElementById("search-competences-input")
        .addEventListener("keydown", function (e) {
            if (e.keyCode === 13) {
                // 13 es el código de tecla para "Intro"
                filterTree(e.target.value);
            }
        });

    let typingTimer; // Almacena el temporizador de espera
    const doneTypingInterval = 500;

    document.addEventListener("input", function (e) {
        if (e.target.matches("#search-competences-input")) {
            clearTimeout(typingTimer); // Limpia el temporizador anterior para reiniciar el retraso
            typingTimer = setTimeout(function () {
                filterTree(e.target.value); // Ejecuta después del retraso
            }, doneTypingInterval);
        }
    });

    document
        .getElementById("clean-search")
        .addEventListener("click", function () {
            document.getElementById("search-competences-input").value = "";
            filterTree("");
        });

    document
        .getElementById("btn-delete-competences")
        .addEventListener("click", function () {
            deleteSelectedCompetences();
        });

    document
        .getElementById("competence-form")
        .addEventListener("submit", submitFormCompetence);

    document
        .getElementById("new-competence-framework-btn")
        .addEventListener("click", newCompetenceFramework);

    document
        .getElementById("learning-result-form")
        .addEventListener("submit", submitLearningObjectForm);

    document
        .getElementById("import-csv-btn")
        .addEventListener("click", function () {
            showModal(
                "import_competence_framework-modal",
                "Importar competencias"
            );
        });

    document
        .getElementById("btn-export-import")
        .addEventListener("click", function () {
            showModal("export_import-modal", "Exportar/Importar");
        });

    document
        .getElementById("import-esco-framework")
        .addEventListener("click", function () {
            showModal("import-esco-framework-modal", "Importar Marco ESCO");
        });

    document
        .getElementById("esco-framework-form")
        .addEventListener("submit", submitEscoFrameworkForm);

    document
        .getElementById("competence-framework-form")
        .addEventListener("submit", submitCompetencesFrameworkForm);

    updateInputImage();

    document.body.addEventListener("click", function (event) {
        if (event.target.closest(".infinite-tree-item")) {
            handleNodeClick(event);
        }
    });

    document
        .getElementById("import-framework-form")
        .addEventListener("submit", submitImportFrameworkForm);

    document
        .getElementById("btn-export")
        .addEventListener("click", function () {
            exportCSV();
        });

    document
        .getElementById("has_levels")
        .addEventListener("click", function () {
            addTemplate();
        });

    document
        .getElementById("btn-add-level")
        .addEventListener("click", function () {
            addLevel();
        });

    document
        .getElementById("level-list")
        .addEventListener("click", removeLevel);
}

function submitCompetencesFrameworkForm() {
    const formData = new FormData(this);

    const hasLevels = document.getElementById("has_levels");
    formData.append("has_levels", hasLevels.checked ? 1 : 0);

    const levels = getLevels();
    formData.append("levels", JSON.stringify(levels));

    const params = {
        url: "/cataloging/competences_learnings_results/save_competence_framework",
        method: "POST",
        body: formData,
        loader: true,
        toast: true,
    };

    const opennedNodes = treeCompetencesLearningResults.getOpenNodes();

    resetFormErrors("competence-framework-form");
    apiFetch(params)
        .then(() => {
            hideModal("competence-framework-modal");

            reloadTreeCompetencesLearningResults(opennedNodes);
        })
        .catch((data) => {
            showFormErrors(data.errors, "competence-framework-form");
        });
}

function handleNodeClick(event) {
    const nodeId = event.target.closest(".infinite-tree-item").dataset.id;
    const node = treeCompetencesLearningResults.getNodeById(nodeId);

    const addLearningResultBtn = event.target.closest(
        ".add-learning-result-btn"
    );

    const addCompetenceBtn = event.target.closest(".add-competence-btn");

    const editLearningResultBtn = event.target.closest(
        ".edit-learning-result-btn"
    );
    const editNodeBtn = event.target.closest(".edit-node-btn");

    if (addLearningResultBtn) {
        newLearningResult(nodeId);
    } else if (editLearningResultBtn) {
        loadLearningResultModal(nodeId);
    } else if (addCompetenceBtn) {
        if (node.type == "competence_framework") newCompetence(null, nodeId);
        else newCompetence(nodeId);
    } else if (editNodeBtn) {
        if (node.type === "competence_framework")
            loadCompetenceFrameworkModal(nodeId);
        else loadCompetenceModal(nodeId);
    }
}

async function reloadTreeCompetencesLearningResults(openNodes = []) {
    await loadTreeCompetencesLearningResults();
    treeCompetencesLearningResults.loadData(competencesLearningResults);

    openNodes.forEach((node) => {
        const n = treeCompetencesLearningResults.getNodeById(node.id);
        treeCompetencesLearningResults.openNode(n);
    });

    instanceSelectedCompetencesLearningResults();
}

async function loadTreeCompetencesLearningResults() {
    function mapStructure(obj, type) {
        // Crear un nuevo objeto con los campos necesarios
        const mappedObj = {
            id: obj.uid,
            name: obj.name,
            description: obj.description,
            children: [],
            type: type,
            showCheckbox: true,
            disabled: false,
            buttons: [
                {
                    className: "edit-node-btn",
                    icon: heroicon("pencil", "outline"),
                    title:
                        type == "competence_framework"
                            ? "Editar marco de competencias"
                            : "Editar competencia",
                },
                {
                    className: "add-competence-btn",
                    icon: heroicon("folder-plus", "outline"),
                    title: "Añadir competenciaaa",
                },
            ],
        };

        if (type == "competence") {
            mappedObj.buttons.push({
                className: "add-learning-result-btn",
                icon: heroicon("plus", "outline"),
                title: "Añadir resultado de aprendizaje",
            });
        }

        if (obj.competences && obj.competences.length > 0) {
            obj.competences.forEach((comp) => {
                mappedObj.children.push(mapStructure(comp, "competence"));
            });
        }

        // Si hay subcompetencias, recursivamente mapéalas
        if (obj.all_subcompetences && obj.all_subcompetences.length > 0) {
            obj.all_subcompetences.forEach((sub) => {
                mappedObj.children.push(mapStructure(sub, "competence"));
            });
        }

        // Si hay resultados de aprendizaje, agrégalos también
        if (obj.learning_results && obj.learning_results.length > 0) {
            obj.learning_results.forEach((lr) => {
                mappedObj.children.push({
                    id: lr.uid,
                    name: lr.name,
                    description: lr.description,
                    type: "learning_result",
                    showCheckbox: true,
                    buttons: [
                        {
                            className: "edit-learning-result-btn",
                            icon: heroicon("pencil", "outline"),
                            title: "Editar resultado de aprendizaje",
                        },
                    ],
                });
            });
        }

        // Devuelve el objeto mapeado
        return mappedObj;
    }

    const params = {
        url: "/cataloging/competences_learnings_results/get_all_competences",
        method: "GET",
        loader: true,
    };

    const data = await apiFetch(params);

    const structure = [];

    data.forEach((competenceFramework) => {
        structure.push(
            mapStructure(competenceFramework, "competence_framework")
        );
    });

    competencesLearningResults = structure;
}

function instanceTreeCompetences() {
    const updateCheckboxState = (treeCompetencesLearningResults) => {
        const checkboxes =
            treeCompetencesLearningResults.contentElement.querySelectorAll(
                'input[type="checkbox"]'
            );

        // Si el bloque está deshabilitado, deshabilitamos todos los checkboxes
        for (let i = 0; i < checkboxes.length; ++i) {
            const checkbox = checkboxes[i];
            if (checkbox.hasAttribute("data-indeterminate")) {
                checkbox.indeterminate = true;
            } else {
                checkbox.indeterminate = false;
            }
        }
    };

    treeCompetencesLearningResults = new InfiniteTree(
        document.getElementById("tree-competences-learning-results"),
        {
            rowRenderer: renderer,
            selectable: false,
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

        if (!currentNode || !event.target.className.includes("checkbox")) return;

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
        JSON.stringify(competencesLearningResults)
    );

    treeCompetencesLearningResults.loadData(competencesLearningResultsCopy);
}

function updateSelectedCompetencesAndLearningResults(currentNode) {
    // Sacamos los hijos
    function getChildNodesCompetences(node) {
        let resultArray = [];

        if (!node.children.length) return resultArray;

        node.children.forEach((child) => {
            // Si el nodo cumple con la condición, añadir al array
            if (child.type === "competence") {
                resultArray.push(child.id);
            }

            resultArray = resultArray.concat(getChildNodesCompetences(child));
        });

        return resultArray;
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

    const { id, type, state } = currentNode;
    const isSelected = state.checked;
    const isCompetence = type === "competence";
    const isLearningResult = type === "learning_result";

    // Función para agregar o eliminar competencias/learningResults
    function updateArray(array, items, add) {
        if (add) {
            array.push(id);
            return array.concat(items);
        } else {
            return array.filter((item) => item !== id && !items.includes(item));
        }
    }

    // Obtener los nodos hijos según el tipo
    const childCompetences = getChildNodesCompetences(currentNode);
    const childLearningResults = getChildNodesLearningResults(currentNode);

    if(type == "competence_framework"){
        if(isSelected) selectedCompetencesLearningResults.competencesFrameworks.add(id);
        else selectedCompetencesLearningResults.competencesFrameworks.delete(id);
    }
    else if (isCompetence) {
        if(isSelected) {
            selectedCompetencesLearningResults.competences.add(id);
            selectedCompetencesLearningResults.learningResults.add(...childCompetences);
        } else {
            selectedCompetencesLearningResults.competences.delete(id);
            selectedCompetencesLearningResults.learningResults.delete(...childCompetences);
        }
    } else if (isLearningResult) {
        if(isSelected) selectedCompetencesLearningResults.learningResults.add(...childLearningResults);
        else selectedCompetencesLearningResults.learningResults.delete(...childLearningResults);
    }
}

function loadLearningResultModal(learningResultUid) {
    const params = {
        url:
            "/cataloging/competences_learnings_results/get_learning_result/" +
            learningResultUid,
        method: "GET",
        loader: true,
    };

    apiFetch(params).then((data) => {
        const fillFieldsForm = {
            ...data,
            learning_result_uid: data.uid,
        };
        fillFormWithObject(fillFieldsForm, "learning-result-form");

        showModal("learning-result-modal", "Editar resultado de aprendizaje");
    });
}

function newLearningResult(competenceUid) {
    resetFormFields("learning-result-form");

    const fieldsLearningResultForm = {
        competence_uid: competenceUid,
    };

    fillFormWithObject(fieldsLearningResultForm, "learning-result-form");

    showModal("learning-result-modal", "Nuevo resultado de aprendizaje");
}

function submitLearningObjectForm() {
    const formData = new FormData(this);

    const params = {
        url: "/cataloging/competences_learnings_results/save_learning_result",

        method: "POST",
        body: formData,
        loader: true,
        toast: true,
    };

    const opennedNodes = treeCompetencesLearningResults.getOpenNodes();

    apiFetch(params).then(() => {
        hideModal("learning-result-modal");
        reloadTreeCompetencesLearningResults(opennedNodes);
    });
}

function submitEscoFrameworkForm() {
    resetFormErrors("esco-framework-form");

    const formData = new FormData(this);

    const params = {
        url: "/cataloging/competences_learnings_results/import_esco_framework",
        method: "POST",
        body: formData,
        loader: true,
        toast: true,
    };

    resetFormErrors("learning-result-form");

    apiFetch(params)
        .then(() => {
            hideModal("import_competence_framework-modal");
            hideModal("import-esco-framework-modal");
            reloadTreeCompetencesLearningResults();
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

async function filterTree(textToSearch) {
    closeAllNodes(treeCompetencesLearningResults.getRootNode());

    treeCompetencesLearningResults.filter((node) => {
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
            treeCompetencesLearningResults.openNode(node);
            openNode(node.parent);
        }
    }

    function closeAllNodes(node) {
        node.children.forEach((child) => {
            closeAllNodes(child);
            if (child.state.open)
                treeCompetencesLearningResults.closeNode(child);
        });
    }
}

/**
 * Maneja el envío del formulario para crear/editar una competencia.
 */
function submitFormCompetence() {
    const formData = new FormData(this);

    const params = {
        url: "/cataloging/competences_learnings_results/save_competence",
        method: "POST",
        body: formData,
        loader: true,
        toast: true,
    };

    const opennedNodes = treeCompetencesLearningResults.getOpenNodes();
    resetFormErrors("competence-form");



    apiFetch(params)
        .then(() => {
            hideModal("competence-modal");
            reloadTreeCompetencesLearningResults(opennedNodes);
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

/**
 * Inicializa el botón para crear una nueva competencia y abre el modal correspondiente.
 */
async function newCompetence(
    competenceFatherUid = null,
    competenceFrameworkUid = null
) {
    resetFormFields("competence-form");
    resetFormErrors("competence-form");

    document.getElementById("parent_competence_uid").value =
        competenceFatherUid;

    document.getElementById("competence_framework_uid").value =
        competenceFrameworkUid;

    showModal("competence-modal", "Nueva competencia");
}

function newCompetenceFramework() {
    resetFormFields("competence-framework-form");
    document.getElementById("has_levels").checked = false;
    document.getElementById("level-list").innerHTML = "";
    document.getElementById("level").classList.add("hidden");
    showModal("competence-framework-modal", "Nuevo marco de competencias");
}

/**
 * Carga el modal para editar una competencia.
 * @param {string} competenceUid - El UID de la competencia a editar. Null para una nueva competencia.
 */
async function loadCompetenceModal(competenceUid = null) {
    const params = {
        url:
            "/cataloging/competences_learnings_results/get_competence/" +
            competenceUid,
        method: "GET",
        loader: true,
    };

    resetFormFields("competence-form");
    resetFormFields("competence-framework-form");

    apiFetch(params).then((response) => {
        const data = {
            ...response,
            competence_uid: response.uid,
        };

        fillFormWithObject(data, "competence-form");
        showModal("competence-modal", "Editar competencia");
    });
}

function loadCompetenceFrameworkModal(competenceFrameworkUid) {
    const params = {
        url:
            "/cataloging/competences_learnings_results/get_competence_framework/" +
            competenceFrameworkUid,
        method: "GET",
        loader: true,
    };

    apiFetch(params).then((data) => {
        document.getElementById("has_levels").checked = data.has_levels;
        document.getElementById("competence_framework_modal_uid").value = data.uid;


        fillFormWithObject(data, "competence-framework-form");
        loadLevels(data['levels'], data['uid']);
        showModal("competence-framework-modal", "Editar marco de competencias");
    });
}

/**
 * Elimina las competencias seleccionadas.
 */
async function deleteSelectedCompetences() {
    // Get all checked checkboxes

    if (
        !selectedCompetencesLearningResults.competencesFrameworks.size &&
        !selectedCompetencesLearningResults.competences.size &&
        !selectedCompetencesLearningResults.learningResults.size
    ) {
        showToast("No has seleccionado ningún elemento", "error");
        return;
    }

    // Mostramos modal de confirmación
    showModalConfirmation(
        "¿Deseas eliminar las competencias y resultados de aprendizaje seleccionados?",
        "Esta acción no se puede deshacer."
    ).then((result) => {
        if (result) {
            deleteCompetencesLearningResults();
        }
    });
}

/**
 * Realiza la operación de eliminación de competencias en el servidor.
 * @param {Array} competenceUids - Un array de UIDs de las competencias a eliminar.
 */
async function deleteCompetencesLearningResults() {
    // Convertir los sets a arrays
    const competencesFrameworksArray = Array.from(selectedCompetencesLearningResults.competencesFrameworks);
    const competencesArray = Array.from(selectedCompetencesLearningResults.competences);
    const learningResultsArray = Array.from(selectedCompetencesLearningResults.learningResults);

    // Crear el objeto que se enviará en el cuerpo de la solicitud
    const body = {
        competencesFrameworks: competencesFrameworksArray,
        competences: competencesArray,
        learningResults: learningResultsArray,
    };

    const params = {
        url: "/cataloging/competences_learnings_results/delete_competences_learning_results",
        method: "DELETE",
        body: { uids: body },
        stringify: true,
        loader: true,
        toast: true,
    };

    const opennedNodes = treeCompetencesLearningResults.getOpenNodes();

    apiFetch(params).then(() => {
        reloadTreeCompetencesLearningResults(opennedNodes);
    });
}

function instanceSelectedCompetencesLearningResults() {
    selectedCompetencesLearningResults = {
        competencesFrameworks: new Set(),
        competences: new Set(),
        learningResults: new Set(),
    };
}

function exportCSV() {
    const params = {
        url: "/cataloging/export_csv",
        method: "GET",
        loader: true,
    };

    apiFetch(params).then((data) => {
        var jsonString = JSON.stringify(data, null, 2);
        var blob = new Blob([jsonString], { type: "application/json" });
        var downloadLink = document.createElement("a");
        downloadLink.href = window.URL.createObjectURL(blob);
        downloadLink.download = "data.json";
        downloadLink.innerHTML = "Descargar JSON";
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    });
}

function submitImportFrameworkForm() {
    const formData = new FormData(this);

    const params = {
        url: "/cataloging/import_csv",
        method: "POST",
        body: formData,
        loader: true,
        toast: true,
    };

    apiFetch(params)
        .then(() => {
            hideModal("export_import-modal");
            reloadTreeCompetencesLearningResults();
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

function addTemplate(){

    const hasLevel = document.getElementById("has_levels").checked;
    if (hasLevel == true){
        const levelList = document.getElementById("level-list").innerHTML;
        if (levelList == ""){
            addLevel();
        }
        document.getElementById("level").classList.remove("hidden");
    }else{
        document.getElementById("level").classList.add("hidden");
    }
}

function addLevel() {
    const template = document
        .getElementById("level-template")
        .content.cloneNode(true);
    document.getElementById("level-list").appendChild(template);
}

function removeLevel(event) {

    var elements = document.getElementsByClassName('level');
    var count = elements.length;

    if (count > 1){
        let target = event.target;

        if (!target.classList.contains(".btn-remove-level")) {
            target = target.closest(".btn-remove-level");
        }

        if (target) {
            target.closest(".level").remove();
        }
    }
}

function getLevels(){

    const levels = document
        .getElementById("level-list")
        .querySelectorAll(".level");

    const levelsData = [];

    levels.forEach((level) => {
        let levelData = {
            uid: level.dataset.levelUid ?? null,
            name: level.querySelector(".level-name").value,
        };

        levelsData.push(levelData);
    });

    return levelsData;

}

function loadLevels(levels, uid){

        const containerLevels = document.getElementById("level-list");
        containerLevels.innerHTML = "";


        levels.forEach((level) => {
            const levelTemplate = document
                .getElementById("level-template")
                .content.cloneNode(true);

            levelTemplate.querySelector(".level").dataset.levelUid = level.uid;

            levelTemplate.querySelector(".level-name").value = level.name;

            containerLevels.appendChild(levelTemplate);

            document.getElementById("level").classList.remove("hidden");

        });
}
