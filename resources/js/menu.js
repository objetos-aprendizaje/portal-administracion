import { setCookie, getCookie } from "./cookie_handler.js";

document.addEventListener("DOMContentLoaded", function () {
    setupMainMenuHoverBehavior();
    handleSubmenuPositioning();

    // Botón para contraer o desplegar menú
    var toggleButton = document.getElementById("collapse-expand-menu-btn");
    toggleButton.addEventListener("click", function () {
        toggleMenu();
    });

    // Sacamos de caché el estado del menú
    const menuStatusCookie = getCookie("menuStatus");
    setMenuStatus(menuStatusCookie);

    const menuStatusCookieShowHide = getCookie('menuStatusShowHide');
    setMenuStatusShowHide(menuStatusCookieShowHide);


    //boton para ocultar o desocultar el menú
    var showHideButton = document.getElementById("toggle-menu-btn");
    showHideButton.addEventListener("click", function () {
        showHideMenu();
    });

});

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
                // Calcula la altura del elemento del menú
                var menuOptionHeight = this.offsetHeight;
                // Calcula la posición en la que se desplegaría el submenú hacia abajo
                var position =
                    this.getBoundingClientRect().bottom + submenuHeight;
                // Calcula la posición en la que se desplegaría el submenú hacia arriba
                var positionTop =
                    this.getBoundingClientRect().top - submenuHeight;

                if (positionTop < 0) {
                    // Si el submenú se desborda por la parte superior, despliégalo hacia abajo
                    submenu.style.top = `${menuOptionHeight - menuOptionHeight}px`;
                } else if (position > window.innerHeight) {
                    // Si el submenú se desborda por la parte inferior, despliégalo hacia arriba
                    submenu.style.top = `-${submenuHeight - menuOptionHeight}px`;
                } else {
                    // Si el submenú no se desborda, despliégalo hacia abajo
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

/**
 * Contrae o despliega el menú principal en función del estado actual
 */
function toggleMenu() {
    const menu = document.querySelector(".menu");

    if (menu.classList.contains("menu-collapsed")) {
        setMenuStatus("menu-non-collapsed");
    } else if (menu.classList.contains("menu-non-collapsed")) {
        setMenuStatus("menu-collapsed");
    }
}

/**
 * Oculta o muestra el menú principal en función del estado actual
 */
function showHideMenu() {
    const menu = document.querySelector(".menu");

    if (menu.classList.contains("menu-show")) {
        setMenuStatusShowHide("menu-hide");
    }

    else if (menu.classList.contains("menu-hide")) {
        setMenuStatusShowHide("menu-show");
    }
}


/**
 * Establece el estado del menú principal y lo guarda en cookie
 */
function setMenuStatus(status) {
    const menu = document.querySelector(".menu");
    const arrowLeft = document.getElementById("arrow-left");
    const arrowRight = document.getElementById("arrow-right");

    if (status == "menu-collapsed") {
        menu.classList.add("menu-collapsed");
        menu.classList.remove("menu-non-collapsed");
        arrowLeft.classList.remove("hidden");
        arrowRight.classList.add("hidden");
    } else {
        menu.classList.add("menu-non-collapsed");
        menu.classList.remove("menu-collapsed");
        arrowLeft.classList.add("hidden");
        arrowRight.classList.remove("hidden");
    }
    document.getElementById(
        "main-content"
    ).style.marginLeft = `${menu.offsetWidth}px`;

    setCookie("menuStatus", status);
}

/**
 * Establece el estado del menú principal y lo guarda en cookie
*/
function setMenuStatusShowHide(status) {

    const menu = document.querySelector(".menu");


    if (status == "menu-hide") {
        menu.classList.add("menu-hide");
        menu.classList.remove("menu-show");
        document.getElementById("main-content").style.marginLeft = `0px`;
    }else{
        menu.classList.add("menu-show");
        menu.classList.remove("menu-hide");
        document.getElementById("main-content").style.marginLeft = `${menu.offsetWidth}px`;
    }

    setCookie('menuStatusShowHide', status);

}
