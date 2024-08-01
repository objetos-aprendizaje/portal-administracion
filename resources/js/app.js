window.defaultImagePreview =
    "/data/images/default_images/no_image_attached.svg";
window.defaultErrorMessageFetch = "Ha ocurrido un error";

import TomSelect from "tom-select";
import { Spanish } from "flatpickr/dist/l10n/es.js";
import flatpickr from "flatpickr";
import tag from "html5-tag";

import { showToast } from "./toast";
import { heroicon } from "./heroicons";
document.addEventListener("DOMContentLoaded", function () {
    // Llamada inicial a adjustPadding para configurar el padding en el inicio
    adjustPadding();
    controlClickOutsideMoreOptionsTable();
    applyPreventDefaultForms();

    getAllLabelsOfPage()
});

window.addEventListener("resize", function () {
    adjustPadding();
});

export function accordionControls() {
    var accordionHeaders = document.querySelectorAll(".accordion-header");

    accordionHeaders.forEach(function (header) {
        header.addEventListener("click", function () {
            var content = this.nextElementSibling;

            // Alternar la clase del contenido del acordeón
            content.classList.toggle("accordion-collapsed");
            content.classList.toggle("accordion-uncollapsed");

            var rotateIcon = this.querySelector(".rotate-icon");

            // Alternar la rotación del ícono
            if (content.classList.contains("accordion-collapsed")) {
                rotateIcon.style.transform = "rotate(0deg)";
            } else {
                rotateIcon.style.transform = "rotate(180deg)";
            }
        });
    });
}

function controlClickOutsideMoreOptionsTable() {
    document.addEventListener(
        "click",
        function (event) {
            // Esto comprobará si el clic fue fuera de un menú existente.
            let isClickInsideMenu = event.target.closest(".options-div");
            let isClickInsideButton =
                event.target.classList.contains("action-btn");

            if (!isClickInsideMenu && !isClickInsideButton) {
                // Cerrar todos los menús abiertos si se hace clic fuera de ellos.
                const menus = document.querySelectorAll(".options-div");
                menus.forEach((menu) => menu.remove());
            }
        },
        false
    );
}

export function showLoader() {
    document.getElementById("fullscreen-loader").style.display = "flex";
    document.body.style.overflow = "hidden"; // Deshabilita el scroll
}

export function hideLoader() {
    document.getElementById("fullscreen-loader").style.display = "none";
    document.body.style.overflow = ""; // Restaura el scroll
}

/**
 * Ajusta el padding superior del cuerpo de la página para tener en cuenta la altura del navbar.
 * Esto evita que el contenido quede oculto debajo del navbar.
 */
function adjustPadding() {
    var navbar = document.getElementById("poa-header");
    var body = document.body;
    var navbarHeight = navbar.offsetHeight;
    body.style.paddingTop = navbarHeight + "px";

    // Configuramos min-height para el contenido principal con calc, restándole el padding top a 100vh
    var mainContent = document.getElementById("main-content");
    mainContent.style.minHeight = `calc(100vh - ${navbarHeight}px)`;
}

export function validateForm(formId) {
    let isValid = true;

    const requiredInputs = document.querySelectorAll(
        `#${formId} input.required`
    );

    requiredInputs.forEach((input) => {
        const value = input.value.trim();

        if (!value) isValid = false;
    });

    return isValid;
}

export function wipeInputsModal(modal) {
    const form = modal.querySelector("form");
    if (form) {
        form.reset();
    }
}

export function formatDateTime(datetime) {
    const date = new Date(datetime);
    const day = date.getDate().toString().padStart(2, "0");
    const month = (date.getMonth() + 1).toString().padStart(2, "0");
    const year = date.getFullYear();
    const hours = date.getHours().toString().padStart(2, "0");
    const minutes = date.getMinutes().toString().padStart(2, "0");

    return `${day}/${month}/${year} a las ${hours}:${minutes}`;
}

/**
 *
 * @param {*} datetime
 * @returns {string} - Fecha en formato "dd/mm/yyyy".
 */
export function formatDate(datetime) {
    const date = new Date(datetime);
    const day = date.getDate().toString().padStart(2, "0");
    const month = (date.getMonth() + 1).toString().padStart(2, "0");
    const year = date.getFullYear();

    return `${day}/${month}/${year}`;
}

export function getCsrfToken() {
    return document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute("content");
}

export function loadJSONFile(path) {
    return fetch(path)
        .then((response) => response.json())
        .catch((error) => console.error(error));
}

export function showFormErrors(errors) {
    Object.keys(errors).forEach((field) => {
        errors[field].forEach((error) => {
            const element = document.getElementById(field);

            if (!element) return;

            // Crea el div que contendrá los mensajes de error.
            const errorContainer = document.createElement("div");
            errorContainer.classList.add("error-container");

            // Crea el mensaje de error y lo añade al div.
            const small = document.createElement("small");
            small.textContent = error;
            small.classList.add("error-label");
            errorContainer.appendChild(small);

            if (element.tagName === "INPUT" && element.type === "file") {
                const div = element.closest(".select-file-container");
                div.classList.add("error-border");
                div.parentNode.insertBefore(errorContainer, div.nextSibling);
            } else if (element.getAttribute("data-choice")) {
                const choicesContainer = element.closest(".choices");
                const choicesInnerContainer =
                    element.closest(".choices__inner");
                if (choicesContainer) {
                    choicesInnerContainer.classList.add("error-border");
                    choicesContainer.parentNode.insertBefore(
                        errorContainer,
                        choicesContainer.nextSibling
                    );
                }
            } else if (
                ["INPUT", "TEXTAREA", "SELECT", 'DIV'].includes(element.tagName)
            ) {
                element.classList.add("error-border");
                element.parentNode.appendChild(errorContainer);
            } else if (element.getAttribute("data-tomselect")) {
                const tomSelectContainer = element.closest(".ts-wrap");
                if (tomSelectContainer) {
                    tomSelectContainer.classList.add("error-border");
                    tomSelectContainer.parentNode.appendChild(errorContainer);
                }
            }
        });
    });
}

export function resetFormErrors(formId) {
    const form = document.getElementById(formId);

    if (!form) return;

    // Reset errors for inputs and textareas within the form
    const elementsWithError = form.querySelectorAll(".error-border");
    elementsWithError.forEach((element) => {
        element.classList.remove("error-border");
    });

    // Remove error messages within the form
    const errorMessages = form.querySelectorAll("small.error-label");
    errorMessages.forEach((small) => {
        small.remove();
    });
}

/**
 * Maneja la actualización de una vista previa de imagen y el nombre del archivo en un input "file".
 * Se activa al cambiar el archivo en elementos con la clase "poa-input-image".
 */
export function updateInputImage(maxSizeKb = false) {
    let classDiv = "poa-input-image";
    document.addEventListener("change", function (e) {
        const target = e.target;

        if (target.closest(`.${classDiv}`) && target.type === "file") {
            const previewDiv = target.closest(`.${classDiv}`);
            const img = previewDiv.querySelector("img");
            const span = previewDiv.querySelector(".image-name");

            if (!target.files || !target.files[0]) {
                img.src = defaultImagePreview;
                span.textContent = "Ningún archivo seleccionado";
                return;
            }

            const fileType = target.files[0].type;
            if (!fileType.startsWith("image/")) {
                showToast("El archivo seleccionado no es una imagen", "error");
                return;
            }

            if (maxSizeKb) {
                const maxSizeBytes = (maxSizeKb / 1024) * 1024 * 1024;
                if (target.files[0].size > maxSizeBytes) {
                    showToast(
                        "El archivo seleccionado es demasiado grande",
                        "error"
                    );
                    return;
                }
            }

            const reader = new FileReader();

            reader.onload = function (event) {
                img.src = event.target.result;
            };

            reader.readAsDataURL(target.files[0]);

            span.textContent = target.files[0].name;
        }
    });
}

/**
 * Función para actualizar el nombre del archivo seleccionado en un input de tipo "file".
 * Esta función se liga al evento "change" del documento y busca contenedores con la clase "poa-input-file".
 * Dentro de estos contenedores, busca un elemento <span> con la clase "file-name" y actualiza su contenido
 * con el nombre del archivo seleccionado. Si no hay archivo, muestra "Ningún archivo seleccionado".
 */
export function updateInputFile() {
    let classDiv = "poa-input-file";

    document.addEventListener("change", function (e) {
        const target = e.target;

        if (target.closest(`.${classDiv}`) && target.type === "file") {
            const previewDiv = target.closest(`.${classDiv}`);
            const span = previewDiv.querySelector(".file-name");

            if (target.files && target.files[0]) {
                const fileName = target.files[0].name;
                span.textContent = fileName;
            } else {
                span.textContent = "Ningún archivo seleccionado";
            }
        }
    });
}

export function changeColorColoris(element, color) {
    element.value = color;

    var event = new Event("input", {
        bubbles: true,
        cancelable: true,
    });
    element.dispatchEvent(event);
}

/**
 * Transforma un rango de fechas seleccionado en Flatpickr a un formato estándar.
 * Esta función toma el objeto 'flatpickrDate', el cual contiene las fechas seleccionadas
 * por el usuario en un widget Flatpickr, y devuelve las fechas en un formato
 * 'YYYY-MM-DD'.
 *
 * @param {*} flatpickrDate - Objeto Flatpickr con las fechas seleccionadas. Este objeto
 *                            contiene un array 'selectedDates' que incluye objetos Date.
 * @returns {string[]} Array de cadenas con las fechas formateadas en 'YYYY-MM-DD'.
 *                     Si el usuario seleccionó un rango de fechas, se devolverán dos
 *                     fechas en el array; si solo seleccionó una fecha, el array
 *                     contendrá un solo elemento.
 */
export function getFlatpickrDateRangeSql(flatpickrDate) {
    // Obtiene el rango de fechas seleccionado desde flatpickr
    let dateRange = flatpickrDate.selectedDates;

    // Formatea las fechas a YYYY-MM-DD HH:MM:SS
    let formattedDates = dateRange.map((date) => {
        return date.toISOString().replace("T", " ").substring(0, 19);
    });

    return formattedDates;
}

export function getFlatpickrDateRange(flatpickrDate) {
    // Obtiene el rango de fechas seleccionado desde flatpickr
    let dateRange = flatpickrDate.selectedDates;

    // Formatea las fechas a "dd/MM/YYYY a las HH:mm"
    let formattedDates = dateRange.map((date) => {
        let datePart = date.toLocaleDateString("es-ES", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
        });
        let timePart = date.toLocaleTimeString("es-ES", {
            hour: "2-digit",
            minute: "2-digit",
        });
        return `${datePart} ${timePart}`;
    });

    // Une las fechas con un guión y un espacio
    return formattedDates.join(" - ");
}

/**
 * Esta función crea una nueva instancia de TomSelect con la capacidad de seleccionar múltiples opciones.
 *
 * @param {string} idElement - El ID del elemento select que se transformará en un TomSelect.
 * @returns {TomSelect} Una nueva instancia de TomSelect.
 */
export function getMultipleTomSelectInstance(idElement) {
    const tomSelectInstance = new TomSelect(idElement, {
        plugins: {
            remove_button: {
                title: "Eliminar",
            },
        },
        render: {
            no_results: function (data, escape) {
                return '<div class="no-results">No se encontraron resultados</div>';
            },
        },
    });

    return tomSelectInstance;
}

export function getMultipleFreeTomSelectInstance(idElement) {
    const tomSelectInstance = new TomSelect(idElement, {
        plugins: {
            remove_button: {
                title: "Eliminar",
            },
        },
        create: true,
        createOnBlur: true,
        render: {
            no_results: function (data, escape) {
                return "";
            },
            option_create: function (data, escape) {
                return (
                    '<div class="create">Añadir <strong>' +
                    escape(data.input) +
                    "</strong>&hellip;</div>"
                );
            },
        },
        onItemRemove: function (value) {
            this.removeOption(value);
        },
    });

    return tomSelectInstance;
}

/**
 * Devuelve un objeto TomSelect con la capacidad de introducir múltiples emails
 * @param {*} idElement
 * @returns
 */
export function getMultipleFreeEmailsTomSelectInstance(idElement) {
    return new TomSelect(idElement, {
        plugins: {
            remove_button: {
                title: "Eliminar",
            },
        },
        create: true,
        createOnBlur: true,
        createFilter: function (input) {
            // Expresión regular para validar emails
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(input);
        },
        render: {
            no_results: function (data, escape) {
                // Si el input del usuario no es un email válido, muestra un mensaje
                if (!this.settings.createFilter(data.input)) {
                    return '<div class="no-results">Por favor, introduce un email válido</div>';
                }
                return "";
            },
            option_create: function (data, escape) {
                // Si el input del usuario es un email válido, muestra la opción para añadirlo
                if (this.settings.createFilter(data.input)) {
                    return (
                        '<div class="create">Añadir <strong>' +
                        escape(data.input) +
                        "</strong>&hellip;</div>"
                    );
                }
                return "";
            },
        },
        onItemRemove: function (value) {
            this.removeOption(value);
        },
    });
}

/**
 * Devuelve un listado de labels de opciones seleccionadas de un tomSelect
 * @param {*} tomSelectInstance
 * @returns
 */
export function getOptionsSelectedTomSelectInstance(tomSelectInstance) {
    let selectedItems = tomSelectInstance.items;
    let selectedLabels = selectedItems.map(
        (item) => tomSelectInstance.options[item].text
    );
    let selectedLabelsString = selectedLabels.join(", ");

    return selectedLabelsString;
}

/**
 * Esta función crea una nueva instancia de TomSelect con funcionalidad de búsqueda en vivo.
 * Obtiene los datos del endpoint proporcionado utilizando la consulta ingresada y mapea la respuesta utilizando la función de mapeo proporcionada.
 * La función de mapeo debe tomar una entrada y devolver un objeto con las propiedades 'value' y 'text'.
 *
 * @param {string} idElement - El ID del elemento select que se transformará en un TomSelect.
 * @param {string} endpoint - La URL para obtener los datos cuando el usuario escribe una consulta.
 * @param {function} mapFunction - La función que mapea cada entrada a un objeto con las propiedades 'value' y 'text'.
 * @returns {TomSelect} Una nueva instancia de TomSelect.
 */
export function getLiveSearchTomSelectInstance(
    idElement,
    endpoint,
    mapFunction
) {
    const instanceTomSelect = new TomSelect(idElement, {
        plugins: {
            remove_button: {
                title: "Eliminar",
            },
        },
        render: {
            no_results: function (data, escape) {
                return '<div class="no-results">No se encontraron resultados</div>';
            },
        },
        search: true,
        create: false,
        load: function (query, callback) {
            const url = endpoint + encodeURIComponent(query);
            fetch(url)
                .then((response) => response.json())
                .then((json) => {
                    if (json.length) {
                        const response = json.map(mapFunction);
                        callback(response);
                    } else {
                        callback();
                    }
                })
                .catch(() => {
                    callback();
                });
        },
        onItemAdd: function () {
            this.control_input.value = "";
        },
    });

    return instanceTomSelect;
}

export function getCreateElementsTomSelectInstance(idElement) {
    const instanceTomSelect = new TomSelect("#tags", {
        persist: false,
        createOnBlur: true,
        create: true,
        plugins: {
            remove_button: {
                title: "Eliminar",
            },
        },
        render: {
            no_results: function (data, escape) {
                return "";
            },
            option_create: function (data, escape) {
                return (
                    '<div class="create">Añadir <strong>' +
                    escape(data.input) +
                    "</strong>&hellip;</div>"
                );
            },
        },
    });

    return instanceTomSelect;
}

export function dropdownButtonToogle() {
    // Selecciona todos los botones de menú desplegable
    const dropdownButtons = document.querySelectorAll(".dropdown button");

    dropdownButtons.forEach((button) => {
        // Encuentra el menú desplegable correspondiente al botón
        const dropdownMenu = button.nextElementSibling;

        button.addEventListener("click", (event) => {
            // Primero, cierra todos los menús
            document
                .querySelectorAll(".dropdown .menu-container")
                .forEach((menu) => {
                    if (menu !== dropdownMenu) {
                        menu.classList.add("hidden");
                    }
                });

            // Luego, alterna el menú correspondiente al botón actual
            dropdownMenu.classList.toggle("hidden");

            // Detener la propagación para evitar que se cierre inmediatamente
            event.stopPropagation();
        });
    });

    // Cerrar todos los menús si se hace clic fuera de ellos
    window.addEventListener("click", () => {
        document
            .querySelectorAll(".dropdown .menu-container")
            .forEach((menu) => {
                menu.classList.add("hidden");
            });
    });

    // Opcional: Prevenir que los menús se cierren al hacer clic dentro
    document.querySelectorAll(".dropdown .menu-container").forEach((menu) => {
        menu.addEventListener("click", (event) => {
            event.stopPropagation();
        });
    });
}

/**
 * Crea una instancia de Flatpickr, que es un plugin de JavaScript para seleccionar fechas y horas.
 *
 * @param {string} idElement - El ID del elemento HTML en el que se instanciará Flatpickr.
 * @returns {Object} - Retorna la instancia de Flatpickr.
 *
 * La instancia de Flatpickr se configura con las siguientes opciones:
 * - mode: "range" - Permite seleccionar un rango de fechas.
 * - dateFormat: "d-m-Y H:i" - Configura el formato de la fecha y hora.
 * - enableTime: true - Habilita la selección de la hora.
 * - locale: Spanish - Configura el idioma a español.
 */
export function instanceFlatpickr(idElement) {
    const flatpickrInstance = flatpickr("#" + idElement, {
        mode: "range",
        dateFormat: "d-m-Y H:i",
        enableTime: true,
        locale: Spanish,
        onClose(selectedDates, dateStr, instance) {
            if (selectedDates.length < 2) {
                setTimeout(() => {
                    instance.input.value = "";
                }, 0);
            }
        },
    });

    return flatpickrInstance;
}

export function toggleFormFields(formId, isDisabled = false) {
    // Selecciona el formulario
    const form = document.getElementById(formId);

    // Selecciona todos los elementos de entrada en el formulario
    const inputs = form.querySelectorAll("input, select, textarea, button");

    // Recorre cada elemento de entrada y establece su propiedad 'disabled' en 'true' o 'false'
    inputs.forEach(function (input) {
        // Si el elemento tiene la clase 'close-modal-btn', no lo modifiques
        if (input.classList.contains("close-modal-btn")) return;

        input.disabled = isDisabled;
        input.classList.toggle("element-disabled", isDisabled);
    });
}

/**
 * Activa o desactiva con la propiedad disabled los campos de entrada específicos dentro de un div.
 * Además añade o quita la clase 'element-disabled' a los campos de entrada.
 * @param {*} ids array de ids
 * @param {*} isDisabled boolean
 */
export function setDisabledSpecificDivFields(ids, isDisabled) {
    // Recorre cada id en el array de ids
    ids.forEach(function (id) {
        // Busca el div con el id
        const div = document.getElementById(id);

        div.dataset.disabled = isDisabled ? 1 : 0;
        // Si el div no existe, salta a la siguiente iteración
        if (!div) return;

        // Busca todos los campos de entrada dentro del div
        const inputs = div.querySelectorAll("input, select, textarea, button");

        // Recorre cada campo de entrada y establece su propiedad 'disabled'
        inputs.forEach(function (input) {
            input.disabled = isDisabled;
            input.classList.toggle("element-disabled", isDisabled);
        });
    });
}

/**
 * Activa o desactiva con readOnly los campos de entrada específicos dentro de un div.
 * Además añade o quita la clase 'element-disabled' a los campos de entrada.
 * @param {*} formId
 * @param {*} ids array de ids
 * @param {*} isReadonly boolean
 */
export function setReadOnlyForSpecificFields(formId, ids, isReadonly) {
    // Selecciona el formulario
    const form = document.getElementById(formId);

    // Recorre cada id en el array de ids
    ids.forEach(function (id) {
        // Busca el elemento con el id en el formulario
        const input = form.querySelector(`#${id}`);

        // Si el elemento no existe, salta a la siguiente iteración
        if (!input) return;

        // Establece la propiedad 'disabled' del elemento en 'true' o 'false'
        input.readOnly = isReadonly;
        input.classList.toggle("element-disabled", isReadonly);
    });
}

/**
 * Añade o quita la propieda disabled a los campos de entrada específicos dentro de un formulario.
 * Además añade o quita la clase 'element-disabled' a los campos de entrada.
 * @param {*} formId
 * @param {*} ids array de ids
 * @param {*} isDisabled boolean
 */
export function setDisabledSpecificFormFields(formId, ids, isDisabled) {
    // Selecciona el formulario
    const form = document.getElementById(formId);

    // Recorre cada id en el array de ids
    ids.forEach(function (id) {
        // Busca el elemento con el id en el formulario
        const input = form.querySelector(`#${id}`);

        // Si el elemento no existe, salta a la siguiente iteración
        if (!input) return;

        // Establece la propiedad 'disabled' del elemento en 'true' o 'false'
        input.disabled = isDisabled;
        input.classList.toggle("element-disabled", isDisabled);
    });
}

export function enableFormFields(formId) {
    // Selecciona el formulario
    const form = document.getElementById(formId);

    // Si el formulario no existe, termina la ejecución de la función
    if (!form) return;

    // Selecciona todos los campos de entrada, botones, selects y textareas dentro del formulario
    const elements = form.querySelectorAll("input, button, select, textarea");

    // Recorre cada elemento
    elements.forEach(function (element) {
        // Quita las propiedades 'disabled' y 'readonly'
        element.disabled = false;
        element.readOnly = false;

        // Quita la clase 'element-disabled'
        element.classList.remove("element-disabled");
    });
}

/**
 *
 *
 * @param {*} params
 * @param {*} params.method - Método HTTP a utilizar para la solicitud.
 * @param {*} params.url - URL a la que se enviará la solicitud.
 * @param {*} params.body - Cuerpo de la solicitud.
 * @param {*} params.stringify - Si el cuerpo debe convertirse a una cadena JSON.
 * @param {*} params.loader - Si se debe mostrar el loader mientras se realiza la solicitud.
 * @param {*} params.toast - Si se debe mostrar un mensaje de tostada después de la solicitud.
 *
 * @returns {Promise} - Retorna una promesa que se resuelve con los datos de la solicitud o se rechaza con un mensaje de error.
 */

export function apiFetch(params) {
    let headers = {
        "X-CSRF-TOKEN": getCsrfToken(),
    };

    let fetchOptions = {
        method: params.method,
        headers: params.headers,
    };

    if (params.stringify) headers["Content-Type"] = "application/json";

    if (params.body !== false) {
        fetchOptions.body = params.stringify
            ? JSON.stringify(params.body)
            : params.body;
    }

    if (params.loader) showLoader();

    return new Promise((resolve, reject) => {
        fetch(params.url, {
            body: fetchOptions.body,
            headers: headers,
            method: fetchOptions.method,
        })
            .then((response) => {
                if (params.loader) hideLoader();
                if (params.download) {
                    return response.blob().then((blob) => {
                        var url = window.URL.createObjectURL(blob);
                        var a = document.createElement("a");
                        a.href = url;
                        a.download = params.filename || "download";
                        a.style.display = "none";
                        document.body.appendChild(a);
                        var contentDisposition = response.headers.get(
                            "Content-Disposition"
                        );
                        var filename = contentDisposition
                            ? contentDisposition.split("=")[1]
                            : "download";
                        a.download = filename;
                        a.click();
                        a.remove();
                        resolve();
                    });
                } else {
                    return response.json().then((data) => {
                        if (response.ok) {
                            if (params.toast)
                                showToast(data.message, "success");
                            resolve(data);
                        } else {
                            if (params.toast) showToast(data.message, "error");
                            reject(data);
                        }
                    });
                }
            })
            .catch((error) => {
                let errorMessage = "";

                if (
                    typeof error === "object" &&
                    error !== null &&
                    error.hasOwnProperty("success") &&
                    !error.success
                ) {
                    errorMessage = error.message;
                } else {
                    errorMessage = defaultErrorMessageFetch;
                }

                if (params.toast) showToast(errorMessage, "error");
                reject(errorMessage);
            });
    });
}

function applyPreventDefaultForms() {
    const forms = document.querySelectorAll("form");

    forms.forEach((form) => {
        form.addEventListener("submit", function (event) {
            event.preventDefault();
        });
    });
}

/**
 * resetea todos los campos inputs y los que son de tipo hidden
 * @param {} form
 */
export function resetFormFields(formId) {
    const form = document.getElementById(formId);
    const inputs = form.querySelectorAll("input, select, textarea");

    inputs.forEach(function (input) {
        if (input.type === "hidden") {
            input.value = "";
        } else {
            input.value = "";
        }
    });
}

export function downloadFile(data) {
    const route = "/download_file/" + data;
    var a = document.createElement("a");
    a.href = route;
    a.download = data;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

/**
 * Esta función actualiza los valores de los inputs ocultos correspondientes a los selects.
 */
export function updateInputValuesSelects() {
    document
        .querySelectorAll(".select-container select")
        .forEach(function (select) {
            select.addEventListener("change", function () {
                // Busca el input oculto correspondiente dentro del mismo contenedor
                const hiddenInput =
                    this.parentNode.querySelector("input[type=hidden]");

                // Actualiza el valor del input oculto con el valor seleccionado en el select
                if (hiddenInput) {
                    hiddenInput.value = this.value;
                }
            });
        });
}

/**
 * Esta función inicializa los valores de los inputs ocultos correspondientes a los selects.
 */
export function initializeHiddenInputs() {
    document
        .querySelectorAll(".select-container select")
        .forEach(function (select) {
            // Busca el input oculto correspondiente dentro del mismo contenedor
            const hiddenInput =
                select.parentNode.querySelector("input[type=hidden]");

            // Establece el valor del input oculto con el valor seleccionado en el select
            if (hiddenInput) {
                hiddenInput.value = select.value;
            }
        });
}

export function getFilterHtml(filterKey, filterName, filterOption) {
    return `
    <div class="filter" id="${filterKey}">
        <div>${filterName}: ${filterOption}</div>
        <button data-filter-key="${filterKey}" class="delete-filter-btn">${heroicon(
        "x-mark",
        "outline"
    )}</button>
    </div>
`;
}

/**
 * Recoge un valor de la url
 * @param {*} param
 * @returns
 */
export function checkParamInUrl(param) {
    const urlParams = new URLSearchParams(window.location.search);
    const value = urlParams.get(param);

    return value;
}

export function wipeParamsInUrl() {
    const urlWithoutParams =
        window.location.protocol +
        "//" +
        window.location.host +
        window.location.pathname;
    window.history.replaceState({}, document.title, urlWithoutParams);
}

export function fillFormWithObject(obj, formId) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);

    // Itera sobre las propiedades del objeto
    for (let key in obj) {
        // Si el objeto tiene la propiedad (no es heredada) y el valor no es null
        if (
            obj.hasOwnProperty(key) &&
            obj[key] !== null &&
            typeof obj[key] === "string"
        ) {
            // Establece el valor en formData
            formData.set(key, obj[key]);
        }
    }

    // Itera sobre formData y establece los valores en el formulario
    for (let [name, value] of formData) {
        const input = form.elements[name];
        if (input) {
            if (input.type === "file") continue;
            input.value = value;
        }
    }
}

export function showElement(element, show) {
    if (show) element.classList.remove("hidden");
    else element.classList.add("hidden");
}

function getAllLabelsOfPage(){
    const labels = document.querySelectorAll('label');
    labels.forEach((label, index) => {
        if (label.htmlFor != ""){
            let findElement = findByInputId(tooltiptexts, label.htmlFor)
            if (findElement){
                const div_tooltp_i = tag("div",{
                    'class': 'tooltip-i',
                    'data-tooltip': findElement.uid,
                },`${heroicon("tooltip-i","outline")}`);
                const div_tooltip_text = tag("div",{
                    'class': 'tooltip-texts hidden',
                    'data-tooltip-text': findElement.uid,
                },findElement.description);
                label.insertAdjacentHTML('afterend', div_tooltp_i+div_tooltip_text);
            }
        }
    });
    addEventsToTooltipTexts();
}

function findByInputId(array, inputId) {
    const item = array.find(item => item.input_id === inputId);
    return item ? { uid: item.uid, description: item.description } : null;
}

function addEventsToTooltipTexts(){
    const tooltips = document.getElementsByClassName('tooltip-i');
    Array.from(tooltips).forEach(tooltip => {
        tooltip.addEventListener('mouseover', (event) => {
            const uid = event.target.parentNode.getAttribute('data-tooltip');
            const element = document.querySelector(`[data-tooltip-text="${uid}"]`);
            element.classList.remove('hidden');
            const xPosition = tooltip.offsetLeft + 75;
            element.style.left = `${xPosition}px`;
            element.classList.add("tooltip-before");
            setPseudoElementTop(element, "tooltip-before");
        });
        tooltip.addEventListener('mouseout', () => {
            const uid = event.target.parentNode.getAttribute('data-tooltip');
            const element = document.querySelector(`[data-tooltip-text="${uid}"]`);
            element.classList.add('hidden');
            element.classList.remove("tooltip-before");
        });
    });
}
function setPseudoElementTop(element, uid) {
    const height = (element.offsetHeight/2)-10;
    const style = document.createElement('style');
    style.type = 'text/css';
    const css = `.${uid}::before { top: ${height}px; }`;
    style.appendChild(document.createTextNode(css));
    document.head.appendChild(style);
}

