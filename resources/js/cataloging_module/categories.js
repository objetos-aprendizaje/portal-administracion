import {
    hideModal,
    showModal,
    showModalConfirmation,
} from "../modal_handler.js";
import {
    showFormErrors,
    resetFormErrors,
    updateInputImage,
    changeColorColoris,
    apiFetch,
} from "../app.js";
import { showToast } from "../toast.js";

document.addEventListener("DOMContentLoaded", function () {
    initHandlers();
});

function initHandlers() {
    document
        .getElementById("new-category-btn")
        .addEventListener("click", newCategory);

    document
        .getElementById("category-form")
        .addEventListener("submit", submitFormCategory);

    document
        .getElementById("btn-delete-categories")
        .addEventListener("click", deleteSelectedCategories);
    document
        .getElementById("search-categories-btn")
        .addEventListener("click", function () {
            searchCategories();
        });

    // Cuando se presiona "Intro" en el campo de búsqueda
    document
        .getElementById("search-categories-input")
        .addEventListener("keydown", function (e) {
            if (e.keyCode === 13) {
                // 13 es el código de tecla para "Intro"
                searchCategories();
            }
        });

    updateInputImage();

    initializeEditButtons();

    initializeCategoriesCheckboxs();
}

async function searchCategories() {
    const textToSearch = document.getElementById(
        "search-categories-input"
    ).value;

    const categoriesHtml = await getHtmlListCategories(textToSearch);

    document.getElementById("list-categories").innerHTML = categoriesHtml;
}

/**
 * Inicializa los botones de edición para cada categoría.
 */
function initializeEditButtons() {
    const editButtons = document.querySelectorAll(".edit-btn");
    editButtons.forEach((button) => {
        button.addEventListener("click", async function () {
            const uid = this.getAttribute("data-uid");
            document.getElementById("category_uid").value = uid;
            await loadCategoryModal(uid);
            showModal("category-modal", "Editar categoría");
        });
    });
}

/**
 * Inicializa los checkboxes de categorías y sus hijos.
 */
function initializeCategoriesCheckboxs() {
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

    const checkboxes = document.querySelectorAll(".element-checkbox");
    checkboxes.forEach((checkbox) => {
        checkbox.addEventListener("click", function () {
            checkChildren(this, this.checked);
        });
    });
}

/**
 * Maneja el envío del formulario para crear/editar una categoría.
 */
function submitFormCategory() {
    resetFormErrors("category-form");

    const formData = new FormData(this);

    const params = {
        url: "/cataloging/categories/save_category",
        method: "POST",
        body: formData,
        toast: true,
        loader: true,
    };

    apiFetch(params)
        .then(() => {
            hideModal("category-modal");
            reloadListCategories();
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

/**
 * Inicializa el botón para crear una nueva categoría y abre el modal correspondiente.
 */
function newCategory() {
    loadCategoryModal();
    showModal("category-modal", "Añadir categoría");
}

/**
 * Obtiene la lista de todas las categorías.
 * @return {Array} - Un array de objetos que representan las categorías.
 */
async function getCategories() {
    const params = {
        url: "/cataloging/categories/get_all_categories",
        method: "GET",
    };

    const response = await apiFetch(params);

    return response;
}

async function getCategory(categoryUid) {
    const params = {
        url: "/cataloging/categories/get_category/" + categoryUid,
        method: "GET",
    };

    const response = await apiFetch(params);

    return response;
}

/**
 * Construye las opciones HTML para un elemento select, dada una estructura de categorías anidadas.
 * Esta función utiliza la recursividad para manejar múltiples niveles de anidación, creando una representación visual
 * anidada en el elemento select similar a como lo hace WordPress, con indentaciones para subcategorías.
 */
function buildOptions(categories, level = 0) {
    let options = "";
    const prefix = "- ".repeat(level); // Crear un prefijo con guiones para la indentación

    categories.forEach((category) => {
        // Añadir la opción actual con la indentación adecuada
        options += `<option value="${category.uid}">${prefix}${category.name}</option>`;
        // Si hay subcategorías, hacer una llamada recursiva para añadirlas también
        if (category.subcategories && category.subcategories.length > 0) {
            options += buildOptions(category.subcategories, level + 1);
        }
    });

    return options;
}

/**
 * Carga el modal para crear/editar una categoría.
 * @param {string} categoryUid - El UID de la categoría a editar. Null para una nueva categoría.
 */
async function loadCategoryModal(categoryUid = null) {
    const categories = await getCategories();

    // Machacar el select con nuevas opciones
    let optionsHtml = '<option value="" selected>Ninguna</option>';

    optionsHtml += buildOptions(categories);

    const selectParentCategory = document.getElementById("parent_category_uid");

    selectParentCategory.innerHTML = optionsHtml;

    if (categoryUid) {
        const category = await getCategory(categoryUid);

        // Rellenar campos de texto y textarea
        document.getElementById("name").value = category.name || "";
        document.getElementById("description").value =
            category.description || "";
        document.getElementById("category_uid").value = category.uid || "";
        changeColorColoris(document.getElementById("color"), category.color);

        if (category.parent_category_uid)
            selectParentCategory.value = category.parent_category_uid;

        if (category.image_path)
            document.getElementById("image_path_preview").src =
                "/" + category.image_path;
    } else {
        // Resetear el formulario
        document.getElementById("name").value = "";
        document.getElementById("description").value = "";
        document.getElementById("category_uid").value = "";
        document.getElementById("image_path_preview").src = defaultImagePreview;
        document.getElementById("image-name").innerText =
            "Ningún archivo seleccionado";
        changeColorColoris(document.getElementById("color"), "#fff");
        selectParentCategory.value = "";
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
 * Obtiene la lista de categorías del servidor de forma asincrónica.
 * @return {string} - El HTML de la lista de categorías.
 */
async function reloadListCategories() {
    const html = await getHtmlListCategories();
    document.getElementById("list-categories").innerHTML = html;

    initializeCategoriesCheckboxs();
    initializeEditButtons();
}

/**
 * Obtiene el HTML de la lista de categorías.
 * @return {string} - El HTML de la lista de categorías.
 */
async function getHtmlListCategories(search = false) {
    // Creamos el objeto URL
    const url = new URL(
        "/cataloging/categories/get_list_categories",
        window.location.origin
    );

    if (search) {
        url.searchParams.append("search", search);
    }

    const params = {
        url: url,
        method: "GET",
    };

    const response = await apiFetch(params);

    return response.html;
}

/**
 * Elimina las categorías seleccionadas.
 */
async function deleteSelectedCategories() {
    // Get all checked checkboxes
    const checkedCheckboxes = document.querySelectorAll(
        ".element-checkbox:checked"
    );
    const categoryUids = [];

    checkedCheckboxes.forEach((checkbox) => {
        categoryUids.push(checkbox.id);
    });

    // Check if any categories are selected
    if (categoryUids.length === 0) {
        showToast("No hay categorías seleccionadas", "error");
        return;
    }

    // Mostramos modal de confirmación
    showModalConfirmation(
        "¿Deseas eliminar las categorías seleccionadas?",
        "Esta acción no se puede deshacer."
    ).then((result) => {
        if (result) {
            deleteCategories(categoryUids);
        }
    });
}

/**
 * Realiza la operación de eliminación de categorías en el servidor.
 * @param {Array} categoryUids - Un array de UIDs de las categorías a eliminar.
 */
function deleteCategories(categoryUids) {
    const params = {
        url: "/cataloging/categories/delete_categories",
        method: "DELETE",
        body: { uids: categoryUids },
        toast: true,
        loader: true,
        stringify: true,
    };

    apiFetch(params).then(() => {
        reloadListCategories();
    });
}
