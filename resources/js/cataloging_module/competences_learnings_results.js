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

document.addEventListener("DOMContentLoaded", function () {
    initHandlers();
    updateInputFile();

});

async function initHandlers() {
    document
        .getElementById("search-competences-btn")
        .addEventListener("click", function () {
            searchCompetences();
        });

    // Cuando se presiona "Intro" en el campo de búsqueda
    document
        .getElementById("search-competences-input")
        .addEventListener("keydown", function (e) {
            if (e.keyCode === 13) {
                // 13 es el código de tecla para "Intro"
                searchCompetences();
            }
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
        .getElementById("new-competence-btn")
        .addEventListener("click", newCompetence);

    document
        .getElementById("learning-result-form")
        .addEventListener("submit", submitLearningObjectForm);


    document
    .getElementById("import-csv-btn")
    .addEventListener("click", function () {
        showModal("import_competence_framework-modal", "Importar competencias");
    });

document
    .getElementById("import-esco-framework")
    .addEventListener("click", function () {
        showModal("import-esco-framework-modal", "Importar Marco ESCO");
    });

document
    .getElementById("esco-framework-form")
    .addEventListener("submit", submitEscoFrameworkForm);

    initializeCompetencesCheckboxs();
    updateInputImage();

    document.body.addEventListener("click", function (event) {
        const addLearningResultBtn = event.target.closest(
            ".add-learning-result-btn"
        );
        const editLearningResultBtn = event.target.closest(
            ".edit-learning-result-btn"
        );
        const editCompetenceBtn = event.target.closest(".edit-competence-btn");

        if (addLearningResultBtn) {
            const competenceUid = addLearningResultBtn.dataset.uid;
            newLearningResult(competenceUid);
        } else if (editLearningResultBtn) {
            const learningResultUid = editLearningResultBtn.dataset.uid;
            reloadCompetencesSelect();
            loadLearningResultModal(learningResultUid);
        } else if (editCompetenceBtn) {
            const competenceUid = editCompetenceBtn.dataset.uid;
            loadCompetenceModal(competenceUid);
        }
    });
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

    apiFetch(params)
        .then(() => {
            hideModal("learning-result-modal");
            reloadListCompetences();
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
            hideModal("learning-result-modal");
            hideModal("import_competence_framework-modal");
            reloadListCompetences();
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

async function searchCompetences() {
    const textToSearch = document.getElementById(
        "search-competences-input"
    ).value;

    const competencesHtml = await getHtmlListCompetences(textToSearch);

    document.getElementById("list-competences").innerHTML = competencesHtml;
}

/**
 * Inicializa los checkboxes de competencias y sus hijos.
 */
function initializeCompetencesCheckboxs() {
    const parentCheckboxes = document.querySelectorAll(".parent");

    parentCheckboxes.forEach(function (parentCheckbox) {
        parentCheckbox.addEventListener("change", function () {
            const childCheckboxes = document.querySelectorAll(
                ".child-of-" + parentCheckbox.id
            );
            childCheckboxes.forEach(function (childCheckbox) {
                childCheckbox.checked = parentCheckbox.checked;
            });
        });
    });

    const checkboxes = document.querySelectorAll(".competence-checkbox");
    checkboxes.forEach((checkbox) => {
        checkbox.addEventListener("click", function () {
            checkChildren(this, this.checked);
        });
    });
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

    resetFormErrors("competence-form");

    apiFetch(params)
        .then(() => {
            hideModal("competence-modal");
            reloadListCompetences();
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

/**
 * Inicializa el botón para crear una nueva competencia y abre el modal correspondiente.
 */
async function newCompetence() {
    await reloadCompetencesSelect();

    resetFormFields("competence-form");
    showModal("competence-modal", "Nueva competencia");
}

async function reloadCompetencesSelect() {
    const params = {
        url: "/cataloging/competences_learnings_results/get_all_competences",
        method: "GET",
        loader: true,
    };

    const competences = await apiFetch(params);

    document.getElementById("parent_competence_uid").innerHTML =
        buildOptions(competences);
}

/**
 * Construye las opciones HTML para un elemento select, dada una estructura de competencias anidadas.
 * Esta función utiliza la recursividad para manejar múltiples niveles de anidación, creando una representación visual
 * anidada en el elemento select similar a como lo hace WordPress, con indentaciones para subcategorías.
 */
function buildOptions(competences, level = 0) {
    let options = "";
    const prefix = "- ".repeat(level); // Crear un prefijo con guiones para la indentación

    competences.forEach((competence) => {
        // Añadir la opción actual con la indentación adecuada
        options += `<option value="${competence.uid}">${prefix}${competence.name}</option>`;
        // Si hay subcategorías, hacer una llamada recursiva para añadirlas también
        if (competence.subcompetences && competence.subcompetences.length > 0) {
            options += buildOptions(competence.subcompetences, level + 1);
        }
    });

    return options;
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

    apiFetch(params).then((response) => {
        const data = {
            ...response,
            competence_uid: response.uid
        }
        fillFormWithObject(data, "competence-form");
        showModal("competence-modal", "Editar competencia");
    });
}

/**
 * Marca o desmarca todos los checkboxes hijos de un checkbox padre.
 * @param {HTMLElement} element - El checkbox padre.
 * @param {boolean} isChecked - Indica si el checkbox padre está marcado o no.
 */
function checkChildren(element, isChecked) {
    const children = element.parentElement.querySelectorAll(
        'input[type="checkbox"]:not(:checked)'
    );
    children.forEach((child) => {
        if (child !== element) {
            child.checked = isChecked;
            checkChildren(child, isChecked);
        }
    });
}

/**
 * Obtiene la lista de competencias del servidor de forma asincrónica.
 * @return {string} - El HTML de la lista de competencias.
 */
async function reloadListCompetences() {
    const html = await getHtmlListCompetences();
    document.getElementById("list-competences").innerHTML = html;

    initializeCompetencesCheckboxs();
}

/**
 * Obtiene el HTML de la lista de competencias.
 * @return {string} - El HTML de la lista de competencias.
 */
async function getHtmlListCompetences(search = false) {
    // Creamos el objeto URL
    const url = new URL(
        "/cataloging/competences_learnings_results/get_list_competences",
        window.location.origin
    );

    // Si 'search' está definido y no es un string vacío, lo añadimos a la cadena de consulta
    if (search) {
        url.searchParams.append("search", search);
    }

    const params = {
        url: url,
        method: "GET",
    };

    const data = await apiFetch(params);

    return data.html;
}

/**
 * Elimina las competencias seleccionadas.
 */
async function deleteSelectedCompetences() {
    // Get all checked checkboxes
    const checkedUids = getCheckedUids();

    if(!checkedUids.competences.length && !checkedUids.learningResults.length) {
        showToast("No has seleccionado ninguna competencia", "error");
        return;
    }

    // Mostramos modal de confirmación
    showModalConfirmation(
        "¿Deseas eliminar las competencias seleccionadas?",
        "Esta acción no se puede deshacer."
    ).then((result) => {
        if (result) {
            deleteCompetences(checkedUids);
        }
    });
}

/**
 * Realiza la operación de eliminación de competencias en el servidor.
 * @param {Array} competenceUids - Un array de UIDs de las competencias a eliminar.
 */
async function deleteCompetences(checkedUids) {

    const params = {
        url: "/cataloging/competences_learnings_results/delete_competences_learning_results",
        method: "DELETE",
        body: { uids: checkedUids },
        stringify: true,
        loader: true,
        toast: true,
    };

    apiFetch(params).then(() => {
        reloadListCompetences();
    });
}

function getCheckedUids() {
    let checkedElements = {
        competences: [],
        learningResults: [],
    };
    const checkedCompetencesCheckboxes = document.querySelectorAll(
        ".competence-checkbox:checked"
    );

    const checkedLearningResultsCheckboxes = document.querySelectorAll(
        ".learning-result-checkbox:checked"
    );

    checkedCompetencesCheckboxes.forEach((checkbox) => {
        checkedElements.competences.push(checkbox.id);
    });

    checkedLearningResultsCheckboxes.forEach((checkbox) => {
        checkedElements.learningResults.push(checkbox.id);
    });

    return checkedElements;
}
