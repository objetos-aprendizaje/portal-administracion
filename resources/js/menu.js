import { setCookie, getCookie } from './cookie_handler.js';


/**
 * Maneja el posicionamiento de los submenús en función de su disponibilidad en la pantalla.
 */
function handleSubmenuPositioning() {
    // Selecciona todos los elementos de menú de primer nivel que no sean submenús
    var menuItems = document.querySelectorAll("#main-menu > li:not(.sub-menu)");

    // Itera sobre los elementos de menú
    menuItems.forEach(function (menuItem) {
        menuItem.addEventListener("mouseover", function () {
            // Calcula la altura de la opción de menú actual
            var menuOptionHeight = menuItem.offsetHeight;
            // Encuentra el submenú asociado al elemento de menú
            var submenu = this.querySelector(".sub-menu");

            if (submenu) {
                // Calcula la altura del submenú
                var submenuHeight = submenu.offsetHeight;
                // Calcula la posición en la que se desplegaría el submenú
                var position =
                    this.getBoundingClientRect().bottom + submenuHeight;

                // Verifica si el submenú se desborda de la pantalla
                if (position > window.innerHeight) {
                    // Ajusta el posicionamiento hacia arriba en función de las alturas
                    submenu.style.top = `-${
                        submenuHeight - menuOptionHeight
                    }px`;
                } else {
                    // Mantén el submenú desplegado hacia abajo
                    submenu.style.top = "0px";
                }
            }
        });
    });
}

/**
 * Configura el comportamiento de interacción de mostrar/ocultar submenús en el menú principal.
 */
function setupMainMenuHoverBehavior() {
    // Obtén el elemento del menú principal
    var mainMenu = document.getElementById("main-menu");
    // Selecciona todos los elementos de menú en el menú principal
    var menuItems = mainMenu.querySelectorAll("li");

    // Itera sobre los elementos de menú
    menuItems.forEach(function (menuItem) {
        // Encuentra el submenú asociado al elemento de menú actual
        var subMenu = menuItem.querySelector(".sub-menu");

        // Agrega el evento "mouseover" para mostrar el submenú al pasar el cursor sobre el elemento de menú
        menuItem.addEventListener("mouseover", function () {
            if (subMenu) {
                subMenu.classList.remove("hidden");
            }
        });

        // Agrega el evento "mouseout" para ocultar el submenú al retirar el cursor del elemento de menú
        menuItem.addEventListener("mouseout", function () {
            if (subMenu) {
                subMenu.classList.add("hidden");
            }
        });
    });
}

function toggleMenuCollapse() {
    const menu = document.querySelector(".menu");

    // Comprobar si la clase 'menu-collapsed' ya está aplicada
    const isCollapsed = menu.classList.contains("menu-collapsed");

    if (isCollapsed) {
        // Si está colapsado, cambia a no colapsado
        menu.classList.remove("menu-collapsed");
        menu.classList.add("menu-non-collapsed");

        document.getElementById("arrow-left").classList.remove("hidden");
        document.getElementById("arrow-right").classList.add("hidden");
        setCookie('menuExpanded', 1);
    } else {
        // Si no está colapsado, cambia a colapsado
        menu.classList.remove("menu-non-collapsed");
        menu.classList.add("menu-collapsed");

        document.getElementById("arrow-left").classList.add("hidden");
        document.getElementById("arrow-right").classList.remove("hidden");
        setCookie('menuExpanded', 0);
    }
}

document.addEventListener("DOMContentLoaded", function () {
    setupMainMenuHoverBehavior();
    handleSubmenuPositioning();

    // Botón para contraer o desplegar menú
    var toggleButton = document.getElementById("collapse-expand-menu-btn");
    toggleButton.addEventListener("click", function () {
        toggleMenuCollapse();
    });

    // Mostrar u ocultar menú
    var toggleMenuButton = document.getElementById("toggle-menu-btn");
    var mainMenu = document.getElementById("main-menu");

    toggleMenuButton.addEventListener("click", function () {
        mainMenu.classList.toggle("hidden");
    });

    // Si el menú lo tenía desplegado
    const menuExpandedCookie = getCookie('menuExpanded');
    if(menuExpandedCookie == 1) toggleMenuCollapse();


});
