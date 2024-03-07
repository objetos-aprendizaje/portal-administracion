import { heroicon } from "./heroicons";

/**
 * Configuración base para Tabulator. Incluye ajustes como tipo de AJAX,
 * filtrado, ordenación, paginación, y localización para textos en español.
 */
export const tabulatorBaseConfig = {
    ajaxConfig: "GET",
    ajaxFiltering: true,
    sortMode: "remote",
    pagination: true,
    layout: "fitColumns",
    rowHeight: "auto",
    paginationMode: "remote",
    paginationSize: 10,
    locale: true,
    langs: {
        "es-es": {
            columns: {
                name: "Nombre",
                progress: "Progreso",
                gender: "Género",
                rating: "Calificación",
                col: "Color",
                dob: "Fecha de Nacimiento",
            },
            pagination: {
                first: "Primero",
                first_title: "Primera Página",
                last: "Último",
                last_title: "Última Página",
                prev: "Anterior",
                prev_title: "Página Anterior",
                next: "Siguiente",
                next_title: "Página Siguiente",
                all: "Todos",
            },
            data: {
                loading: "Cargando...",
                error: "Error",
            },
        },
    },
    placeholder: `<div class="no-records-message">${heroicon(
        "magnifying-glass",
        "outline"
    )} No hay elementos que coincidan con tus criterios de búsqueda</div>`,
};

/**
 *
 * @param {*} table instancia de tabulator
 * @param {*} tableId id asignado al div que contiene la tabla
 * Añade controladores de eventos para la paginación de la tabla.
 * Incluye funcionalidades para navegar a la primera, anterior, siguiente,
 * y última página, así como para cambiar el número de registros mostrados por página.
 * @returns
 */
export function controlsPagination(table, tableId) {
    const containerElement = document.querySelector(
        `.pagination[data-table="${tableId}"]`
    );

    // Verificar si el contenedor existe
    if (!containerElement) {
        console.error(`Contenedor con clase ${tableId} no encontrado.`);
        return;
    }

    // Ir a la primera página
    containerElement
        .querySelector(".btn-first-page")
        .addEventListener("click", function () {
            table.setPage(1);
        });

    // Ir a la página anterior
    containerElement
        .querySelector(".btn-previous-page")
        .addEventListener("click", function () {
            if (table.getPage() > 1) table.previousPage();
        });

    // Ir a la página siguiente
    containerElement
        .querySelector(".btn-next-page")
        .addEventListener("click", function () {
            if (table.getPage() < table.getPageMax()) {
                table.nextPage();
            }
        });

    // Ir a la última página
    containerElement
        .querySelector(".btn-last-page")
        .addEventListener("click", function () {
            table.setPage(table.getPageMax());
        });

    // Actualizar la página actual desde el input
    // Actualizar la página actual desde el input
    const currentPageElement = containerElement.querySelector(".current-page");
    if (currentPageElement) {
        currentPageElement.addEventListener("change", function () {
            const pageNumber = parseInt(this.value, 10);
            const maxPage = table.getPageMax();

            if (pageNumber < 1) {
                this.value = 1;
                return;
            } else if (pageNumber <= maxPage) {
                table.setPage(pageNumber);
            } else {
                this.value = table.getPage();
            }
        });
    }

    // Selector de páginas a mostrar
    const numPagesSelectorElement = containerElement.querySelector(
        ".num-pages-selector"
    );
    if (numPagesSelectorElement) {
        numPagesSelectorElement.addEventListener("change", function () {
            const selectedValue = this.value;
            // Actualizar el número de filas por página en Tabulator
            table.setPageSize(selectedValue);
        });
    }
}

/**
 *
 * @param {*} tabulatorTable instancia de Tabulator
 * @param {*} endPointTable endpoint de la API para actualizar
 * @param {*} tableId id asignado al div que contiene la tabla
 * @returns
 * Configura la funcionalidad de búsqueda en la tabla.
 * Permite realizar búsquedas pulsando un botón o al presionar Enter.
 */
export function controlsSearch(tabulatorTable, endPointTable, tableId) {
    const tableContainer = document.querySelector(
        `div.control-search[data-table="${tableId}"]`
    );

    if (!tableContainer) {
        console.error(
            `No se encontró el contenedor para la tabla con ID '${tableId}'`
        );
        return;
    }

    const searchButton = tableContainer.querySelector(".search-table-btn");
    const searchInput = tableContainer.querySelector(".search-table");

    const triggerSearch = () => {
        const searchValue = searchInput ? searchInput.value : "";
        tabulatorTable.replaceData(endPointTable, { search: searchValue });
    };

    if (searchButton) {
        searchButton.addEventListener("click", triggerSearch);
    } else {
        console.warn("Botón de búsqueda no encontrado.");
    }

    if (searchInput) {
        searchInput.addEventListener("keydown", function (e) {
            if (e.keyCode === 13) {
                // 13 es el código de tecla para "Intro"
                triggerSearch();
            }
        });
    } else {
        console.warn("Campo de búsqueda no encontrado.");
    }
}


/**
 *
 * @param {*} tabulatorTable instancia de Tabulator
 * @param {*} response respuesta de tabulator al completar el ajaxResponse
 * @param {*} tableId id asignado al div que contiene la tabla
 * Actualiza la información mostrada sobre la paginación de la tabla,
 * como el número de la página actual y el rango de registros mostrados.
 */
export function updatePaginationInfo(tabulatorTable, response, tableId) {
    const containerElement = document.querySelector(
        `.pagination[data-table="${tableId}"]`
    );

    const currentPage = tabulatorTable.getPage();
    const pageSize = tabulatorTable.getPageSize();
    const start = (currentPage - 1) * pageSize + 1;
    const end = start + response.data.length - 1;

    const currentPageElement = containerElement.querySelector(".current-page");
    if (currentPageElement) {
        currentPageElement.value = currentPage;
    }

    const pageInfoElement = containerElement.querySelector(".page-info");
    if (pageInfoElement) {
        pageInfoElement.innerText = `${start} a ${end} de ${response.total}`;
    }
}


/**
 *
 * @param {*} checkbox
 * @param {*} rowData data recibida de la api
 * @param {*} array
 * @returns
 * Actualiza un array de registros basándose en el estado de un checkbox.
 * Añade o elimina registros del array según el estado del checkbox.
 */

export function updateArrayRecords(checkbox, rowData, array) {
    let updatedArray = [...array]; // Clonar el array para evitar mutaciones
    if (checkbox.checked) {
        if (!updatedArray.some((item) => item.uid === rowData.uid)) {
            updatedArray.push(rowData);
        }
    } else {
        const index = updatedArray.findIndex(
            (item) => item.uid === rowData.uid
        );
        if (index > -1) {
            updatedArray.splice(index, 1);
        }
    }
    return updatedArray;
}

/**
 *
 * @param {*} dateString fecha en formato SQL
 * @returns
 * Formatea una cadena de fecha a un formato específico (dd/mm/yy).
 */
export function formatDate(dateString) {
    const date = new Date(dateString);
    const day = date.getDate().toString().padStart(2, "0");
    const month = (date.getMonth() + 1).toString().padStart(2, "0");
    const year = date.getFullYear().toString().slice(-2);
    return `${day}/${month}/${year}`;
}

/**
 *
 * @param {*} dateString fecha y hora en formato SQL
 * @returns fecha y hora
 */
export function formatDateTime(dateString) {
    const date = new Date(dateString);

    // Formatear la fecha
    const day = date.getDate().toString().padStart(2, "0");
    const month = (date.getMonth() + 1).toString().padStart(2, "0");
    const year = date.getFullYear().toString();

    // Formatear la hora
    const hours = date.getHours().toString().padStart(2, "0");
    const minutes = date.getMinutes().toString().padStart(2, "0");

    // Combinar fecha y hora
    return `${day}/${month}/${year} ${hours}:${minutes}`;
}

/**
 *
 * @param {*} cell
 * @param {*} buttonArray
 * @returns
 * Crea un menú contextual con más opciones para una celda específica de la tabla.
 * Permite agregar botones personalizados al menú, cada uno con una acción específica.
 */
export function moreOptionsBtn(cell, buttonArray) {
    const dataClicked = cell.getRow().getData();

    const uniqueId = dataClicked.uid;

    closeAllExistingOptionsMenus();

    // Eliminar menú existente si corresponde al mismo botón.
    const existingMenu = document.querySelector(
        '.options-div[data-button-id="' + uniqueId.toString() + '"]'
    );
    if (existingMenu) {
        existingMenu.remove();
        return;
    }

    // Crear un nuevo menú contextual.
    const menu = document.createElement("div");
    menu.dataset.buttonId = uniqueId.toString();
    menu.classList.add("options-div", "flex");

    // Rellenar el menú con botones basados en el array proporcionado.
    buttonArray.forEach((buttonConfig) => {
        const buttonElement = document.createElement("button");
        buttonElement.innerHTML = heroicon(
            buttonConfig.icon,
            buttonConfig.type
        );
        // Añadir el atributo 'title' al botón
        if (buttonConfig.tooltip) {
            buttonElement.title = buttonConfig.tooltip;
        }
        buttonElement.addEventListener("click", (event) => {
            event.stopPropagation();
            closeAllExistingOptionsMenus();
            buttonConfig.action(dataClicked);
        });
        menu.appendChild(buttonElement);
    });

    document.body.appendChild(menu);

    // Posicionar el menú contextual.
    const buttonRect = cell.getElement().getBoundingClientRect();
    menu.style.position = "absolute";
    menu.style.top = `${
        buttonRect.top + window.scrollY - menu.offsetHeight - 5
    }px`;
    const left = buttonRect.left - menu.offsetWidth / 2 + buttonRect.width / 2;
    menu.style.left = `${left}px`;
}

export function dropdownMenu(cell, menuOptions) {
    // Crear el contenido del menú.
    const menuContent = document.createElement("div");
    menuContent.classList.add("dropdown-menu-container");

    // Rellenar el menú con opciones basadas en el array proporcionado.
    menuOptions.forEach((option) => {
        console.log('option', option);
        const menuItem = document.createElement("a");
        menuItem.href = option.href || "#";
        menuItem.textContent = option.text;
        menuItem.classList.add("dropdown-item");
        menuItem.addEventListener("click", (event) => {
            event.stopPropagation();
            closeAllExistingOptionsMenus();
            if (option.action) {
                option.action();
            }
        });
        menuContent.appendChild(menuItem);
    });

    document.body.appendChild(menuContent);

    const buttonElement = cell.getElement();
    const buttonRect = buttonElement.getBoundingClientRect();

    menuContent.style.position = "absolute";
    menuContent.style.top = `${buttonRect.bottom - 5}px`;
    menuContent.style.left = `${buttonRect.left}px`;
    menuContent.style.width = `${buttonRect.width}px`;

    // Agregar manejador de eventos global para cerrar el menú
    window.addEventListener(
        "click",
        (event) => {
            if (!menuContent.contains(event.target)) {
                closeAllExistingOptionsMenus();
            }
        },
        { once: true }
    );

}

function closeAllExistingOptionsMenus() {
    const allExistingOptionsMenus = document.querySelectorAll(".options-div");
    allExistingOptionsMenus.forEach((menu) => menu.remove());

    const allExistingDropdownMenus = document.querySelectorAll(".dropdown-menu-container");
    allExistingDropdownMenus.forEach((menu) => menu.remove());
}

/**
 * @param {*} table instancia de Tabulator
 * @param {*} e evento click
 * @param {*} selectedRecords array de registros seleccionados
 * Maneja el evento click en el checkbox de la cabecera de la tabla.
 * Actualiza el estado de los checkbox de cada fila de la tabla.
 */
export function handleHeaderClick(table, e, selectedRecords) {
    const selectAllCheckbox = e.target;
    if (selectAllCheckbox.type === "checkbox") {
        // Asegúrate de que el clic fue en el checkbox
        table.getRows().forEach((row) => {
            const cell = row.getCell("select");
            const checkbox = cell
                .getElement()
                .querySelector('input[type="checkbox"]');
            checkbox.checked = selectAllCheckbox.checked;

            selectedRecords = updateArrayRecords(
                checkbox,
                row.getData(),
                selectedRecords
            );
        });
    }

    return selectedRecords;
}
