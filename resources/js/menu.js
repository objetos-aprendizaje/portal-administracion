import { setCookie, getCookie } from "./cookie_handler.js";

document.addEventListener("DOMContentLoaded", function () {
    setupMainMenuHoverBehavior();

    // Botón para contraer o desplegar menú
    const toggleButton = document.getElementById("collapse-expand-menu-btn");
    toggleButton.addEventListener("click", function () {
        toggleMenu();
    });

    // Sacamos de caché el estado del menú
    const menuStatusCookie = getCookie("menuStatus");
    setMenuStatus(menuStatusCookie);

    const menuStatusCookieShowHide = getCookie("menuStatusShowHide");
    setMenuStatusShowHide(menuStatusCookieShowHide);

    //boton para ocultar o desocultar el menú
    const showHideButton = document.getElementById("toggle-menu-btn");
    showHideButton.addEventListener("click", function () {
        showHideMenu();
    });
});

/**
 * Configura el comportamiento de interacción de mostrar/ocultar submenús en el menú principal.
 */
function setupMainMenuHoverBehavior() {
    // Obtén el elemento del menú principal
    const mainMenu = document.getElementById("main-menu");
    // Selecciona todos los elementos de menú en el menú principal
    const menuItems = mainMenu.querySelectorAll(".container-menu");

    // Itera sobre los elementos de menú
    menuItems.forEach(function (menuItem) {
        // Encuentra el submenú asociado al elemento de menú actual
        const subMenu = menuItem.querySelector(".sub-menu");

        if (subMenu) {
            const closeIcon = menuItem.querySelector(".close-sub-menu");
            const openIcon = menuItem.querySelector(".open-sub-menu");

            const clickObject = menuItem.querySelector("li");
            // Agrega el evento "click" para mostrar y/o ocultar el submenú
            clickObject.addEventListener("click", function () {
                if (subMenu.classList.contains("hidden-opacity")) {
                    subMenu.style.height = subMenu.scrollHeight + "px";
                    subMenu.classList.remove("hidden-opacity");
                    subMenu.classList.add("sub-menu-width");
                    setTimeout(() => {
                        subMenu.style.height = "auto";
                    }, 500); // Duración de la transición en ms

                    closeIcon.classList.add("hidden");
                    openIcon.classList.remove("hidden");
                } else {
                    subMenu.style.height = subMenu.scrollHeight + "px";
                    setTimeout(() => {
                        subMenu.style.height = "0";
                    }, 10); // Pequeño retraso para permitir el repaint antes de colapsar
                    subMenu.classList.add("hidden-opacity");
                    subMenu.classList.remove("sub-menu-width");

                    closeIcon.classList.remove("hidden");
                    openIcon.classList.add("hidden");
                }
            });

            const menuSelected = menuItem.querySelector("li");

            if (menuSelected.classList.contains("menu-element-selected")) {
                subMenu.style.height = subMenu.scrollHeight + "px";
                subMenu.classList.remove("hidden-opacity");
                subMenu.classList.add("sub-menu-width");
                setTimeout(() => {
                    subMenu.style.height = "auto";
                }, 500); // Duración de la transición en ms
            }
        }
    });
}

/**
 * Contrae o despliega el menú principal en función del estado actual
 */
function toggleMenu() {
    const menu = document.querySelector(".menu");

    const closeIcon = document.querySelectorAll(".close-sub-menu");
    const openIcon = document.querySelectorAll(".open-sub-menu");

    if (menu.classList.contains("menu-collapsed")) {
        setMenuStatus("menu-non-collapsed");
        closeIcon.forEach(function (icon) {
            icon.classList.remove("hidden");
        });
        openIcon.forEach(function (icon) {
            icon.classList.add("hidden");
        });
    } else if (menu.classList.contains("menu-non-collapsed")) {
        setMenuStatus("menu-collapsed");
        closeIcon.forEach(function (icon) {
            icon.classList.add("hidden");
        });
        openIcon.forEach(function (icon) {
            icon.classList.add("hidden");
        });
    }
}

/**
 * Oculta o muestra el menú principal en función del estado actual
 */
function showHideMenu() {
    const menu = document.querySelector(".menu");

    if (menu.classList.contains("menu-show")) {
        setMenuStatusShowHide("menu-hide");
    } else if (menu.classList.contains("menu-hide")) {
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
    } else {
        menu.classList.add("menu-show");
        menu.classList.remove("menu-hide");
        document.getElementById(
            "main-content"
        ).style.marginLeft = `${menu.offsetWidth}px`;
    }

    setCookie("menuStatusShowHide", status);
}
