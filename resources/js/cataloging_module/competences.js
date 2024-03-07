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
} from "../app.js";
import { showToast } from "../toast.js";

let competences = [];

document.addEventListener("DOMContentLoaded", function () {
    initHandlers();
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
        .getElementById("parent_competence_uid")
        .addEventListener("change", function () {
            const competenceUid = this.value;
            changeFatherCompetence(competenceUid);
        });

    initializeCompetencesCheckboxs();
    initializeEditButtons();
    updateInputImage();
}

async function searchCompetences() {
    const textToSearch = document.getElementById(
        "search-competences-input"
    ).value;

    const competencesHtml = await getHtmlListCompetences(textToSearch);

    document.getElementById("list-competences").innerHTML = competencesHtml;
}

/**
 * Inicializa los botones de edición para cada competencia.
 */
function initializeEditButtons() {
    const editButtons = document.querySelectorAll(".edit-btn");
    editButtons.forEach((button) => {
        button.addEventListener("click", async function () {
            const uid = this.getAttribute("data-uid");
            document.getElementById("competence_uid").value = uid;
            await loadCompetenceModal(uid);
            showModal("competence-modal", "Editar competencia");
        });
    });
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
        url: "/cataloging/competences/save_competence",
        method: "POST",
        body: formData,
        loader: true,
        toast: true,
    };

    resetFormErrors("competence-form");

    apiFetch(params)
        .then(() => {
            document.getElementById("competence-form").reset();
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
    await loadCompetenceModal();
    showModal("competence-modal", "Nueva competencia");
}

/**
 * Obtiene la lista de todas las competencias.
 * @return {Array} - Un array de objetos que representan las competencias.
 */
async function getCompetences() {
    const params = {
        url: "/cataloging/competences/get_all_competences",
        method: "GET",
    };

    const response = await apiFetch(params);

    return response;
}

async function getCompetence(competenceUid) {
    const params = {
        url: "/cataloging/competences/get_competence/" + competenceUid,
        method: "GET",
    };

    const data = await apiFetch(params);

    return data;
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
 * Carga el modal para crear/editar una competencia.
 * @param {string} competenceUid - El UID de la competencia a editar. Null para una nueva competencia.
 */
async function loadCompetenceModal(competenceUid = null) {
    competences = await getCompetences();

    // Machacar el select con nuevas opciones
    let optionsHtml = '<option value="" selected>Ninguna</option>';

    optionsHtml += buildOptions(competences);

    const selectParentCompetence = document.getElementById(
        "parent_competence_uid"
    );

    selectParentCompetence.innerHTML = optionsHtml;

    if (competenceUid) {
        const competence = await getCompetence(competenceUid);

        // Rellenar campos de texto y textarea
        document.getElementById("name").value = competence.name || "";
        document.getElementById("description").value =
            competence.description || "";
        document.getElementById("competence_uid").value = competence.uid || "";
        document.getElementById("is_multi_select").value =
            competence.is_multi_select;

        if (competence.parent_competence_uid)
            selectParentCompetence.value = competence.parent_competence_uid;

        if (competence.is_multi_select === null) {
            document
                .getElementById("is-multi-select-container")
                .classList.add("hidden");

            document.getElementById("is_multi_select").value = "";
        } else {
            document
                .getElementById("is-multi-select-container")
                .classList.remove("hidden");

            document.getElementById("is_multi_select").value =
                competence.is_multi_select;
        }
    } else {
        // Resetear el formulario
        document.getElementById("name").value = "";
        document.getElementById("description").value = "";
        document.getElementById("competence_uid").value = "";
        document
            .getElementById("is-multi-select-container")
            .classList.remove("hidden");
        document.getElementById("is_multi_select").value = "";
        document
            .getElementById("is-multi-select-container")
            .classList.remove("hidden");

        selectParentCompetence.value = "";
    }
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
    initializeEditButtons();
}

/**
 * Obtiene el HTML de la lista de competencias.
 * @return {string} - El HTML de la lista de competencias.
 */
async function getHtmlListCompetences(search = false) {
    // Creamos el objeto URL
    const url = new URL(
        "/cataloging/competences/get_list_competences",
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
    const checkedCheckboxes = document.querySelectorAll(
        ".element-checkbox:checked"
    );
    const competenceUids = [];

    checkedCheckboxes.forEach((checkbox) => {
        competenceUids.push(checkbox.id);
    });

    // Check if any competences are selected
    if (competenceUids.length === 0) {
        showToast("No has seleccionado ninguna competencia", "error");

        return;
    }

    // Mostramos modal de confirmación
    showModalConfirmation(
        "¿Deseas eliminar las competencias seleccionadas?",
        "Esta acción no se puede deshacer."
    ).then((result) => {
        if (result) {
            deleteCompetences(competenceUids);
        }
    });
}

/**
 * Realiza la operación de eliminación de competencias en el servidor.
 * @param {Array} competenceUids - Un array de UIDs de las competencias a eliminar.
 */
async function deleteCompetences(competenceUids) {
    const params = {
        url: "/cataloging/competences/delete_competences",
        method: "DELETE",
        body: { uids: competenceUids },
        stringify: true,
        loader: true,
    };

    apiFetch(params).then(() => {
        reloadListCompetences();
    });
}

function changeFatherCompetence(competenceUid) {
    // Buscamos en el array de competencias la competencia padre
    if (competenceUid) {
        const competence = findCompetence(competences, competenceUid);
        document
            .getElementById("is-multi-select-container")
            .classList.toggle("hidden", !competence.is_multi_select);

        if (!competence.is_multi_select) {
            document.getElementById("is_multi_select").value = "";
        }
    } else {
        document
            .getElementById("is-multi-select-container")
            .classList.remove("hidden");
        document.getElementById("is_multi_select").value = "";
    }
}

function findCompetence(competences, competenceUid) {
    for (let competence of competences) {
        if (competence.uid === competenceUid) {
            return competence;
        }

        if (competence.subcompetences) {
            const found = findCompetence(
                competence.subcompetences,
                competenceUid
            );
            if (found) {
                return found;
            }
        }
    }

    return null;
}
