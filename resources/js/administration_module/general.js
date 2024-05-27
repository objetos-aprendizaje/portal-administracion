import {
    updateInputImage,
    updateInputFile,
    accordionControls,
    apiFetch,
    showFormErrors,
    resetFormErrors
} from "../app.js";

document.addEventListener("DOMContentLoaded", function () {
    initHandlers();
    updateInputImage();
    updateInputFile();
    accordionControls();
});

function initHandlers() {
    document
        .getElementById("email-server-form")
        .addEventListener("submit", submitEmailServerForm);
    document
        .getElementById("restore-logo-image-btn")
        .addEventListener("click", submitRestoreLogoImage);
    document
        .getElementById("logo-poa")
        .addEventListener("change", saveLogoImage);
    document
        .getElementById("update-colors-btn")
        .addEventListener("click", saveColors);
    document
        .getElementById("university-info-form")
        .addEventListener("submit", submitUniversityInfoForm);
    document
        .getElementById("general-configuration-form")
        .addEventListener("submit", submitGeneralConfigurationForm);
    document
        .getElementById("save-scripts-btn")
        .addEventListener("click", saveScripts);

    document
        .getElementById("rrss-form")
        .addEventListener("submit", submitRrssForm);

    document
        .getElementById("carrousel-default-config-form")
        .addEventListener("submit", submitCarrouselDefaultForm);

    controlDeleteFonts();

    document.querySelectorAll(".input-font").forEach((element) => {
        element.addEventListener("change", (event) => {
            addFont(event);
        });
    });
}

function saveScripts() {
    const scripts = document.getElementById("scripts-input").value.trim();

    const params = {
        url: "/administration/save_scripts",
        method: "POST",
        body: { scripts: scripts },
        stringify: true,
        loader: true,
        toast: true,
    };

    apiFetch(params);
}

function submitEmailServerForm() {
    const formData = new FormData(this);

    const params = {
        url: "/administration/save_smtp_email_form",
        method: "POST",
        body: formData,
        toast: true,
        loader: true,
    };

    apiFetch(params);
}

async function submitRestoreLogoImage() {
    const params = {
        url: "/administration/restore_logo_image",
        method: "POST",
        loader: true,
        toast: true,
    };

    apiFetch(params).then(() => {
        document
            .getElementById("image-logo-poa-container")
            .classList.add("hidden");
        document
            .getElementById("no-image-logo-poa-container")
            .classList.remove("hidden");
        document.getElementById("image-logo-poa").src = "";
    });
}

function saveLogoImage(event) {
    const file = event.target.files[0];

    const formData = new FormData();
    formData.append("logoPoaFile", file);

    const params = {
        url: "/administration/save_logo_image",
        method: "POST",
        body: formData,
        loader: true,
        toast: true,
    };

    apiFetch(params).then((data) => {
        const route = "/" + data.route;

        document
            .getElementById("image-logo-poa-container")
            .classList.remove("hidden");
        document
            .getElementById("no-image-logo-poa-container")
            .classList.add("hidden");
        document.getElementById("image-logo-poa").src = route;
    });
}

function saveColors() {
    const color1 = document.getElementById("color-1").value;
    const color2 = document.getElementById("color-2").value;
    const color3 = document.getElementById("color-3").value;
    const color4 = document.getElementById("color-4").value;

    const params = {
        url: "/administration/change_colors",
        method: "POST",
        body: {
            color1,
            color2,
            color3,
            color4,
        },
        toast: true,
        stringify: true,
        loader: true,
    };

    apiFetch(params);
}

async function submitUniversityInfoForm() {
    const formData = new FormData(this);

    const params = {
        url: "/administration/save_university_info",
        method: "POST",
        body: formData,
        loader: true,
        toast: true,
    };

    apiFetch(params);
}

function submitRrssForm() {
    const formData = new FormData(this);

    const params = {
        url: "/administration/save_rrss",
        method: "POST",
        body: formData,
        loader: true,
        toast: true,
    };

    apiFetch(params);
}

async function submitGeneralConfigurationForm() {
    const formData = new FormData(this);

    formData.append(
        "operation_by_calls",
        document.getElementById("operation_by_calls").checked ? 1 : 0
    );
    formData.append(
        "learning_objects_appraisals",
        document.getElementById("learning_objects_appraisals").checked ? 1 : 0
    );
    formData.append(
        "payment_gateway",
        document.getElementById("payment_gateway").checked ? 1 : 0
    );

    const params = {
        url: "/administration/save_general_options",
        method: "POST",
        body: formData,
        loader: true,
        toast: true,
    };
    apiFetch(params);
}

function submitCarrouselDefaultForm() {
    const formData = new FormData(this);

    const params = {
        url: "/administration/save_carrousel",
        method: "POST",
        body: formData,
        loader: true,
        toast: true,
    };

    resetFormErrors("carrousel-default-config-form");

    apiFetch(params).catch((data) => {
        showFormErrors(data.errors);
    });;
}

function deleteFont(fontKey) {
    console.log(fontKey)
    const params = {
        url: "/administration/delete_font",
        method: "DELETE",
        body: { fontKey: fontKey },
        stringify: true,
        loader: true,
        toast: true,
    };

    apiFetch(params).then(() => {
        // Eliminamos el .delete-font que tenga el atributo data-font igual a fontKey
        document.querySelectorAll(`.${fontKey}_buttons`).forEach((element) => {
            element.innerHTML = "";
        });

        let input = document.querySelector(`input[data-font="${fontKey}"]`);
        if (input) input.value = "";

        let fileName = document.querySelector(`.file-name.${fontKey}`);
        if (fileName) fileName.innerHTML = "Ningún archivo seleccionado";
    });
}

function addFont(event) {
    let fontKey = event.currentTarget.getAttribute("data-font");
    let file = event.currentTarget.files[0];
    let formData = new FormData();
    formData.append("fontFile", file);
    formData.append("fontKey", fontKey);

    const params = {
        url: "/administration/add_font",
        method: "POST",
        body: formData,
        loader: true,
        toast: true,
    };

    apiFetch(params).then((data) => {
        let downloadLink = `
            <a id="url_${fontKey}" data-font="${fontKey}"
                class="link-label download-font" target="new_blank" href="/${data.fontPath}" download>Descargar</a>
            `;

        let deleteLink = `
            <a data-font="${fontKey}"
                class="link-label delete-font"
                target="new_blank" href="javascript:void(0)">Eliminar</a>
            `;

        document.querySelector(`.${fontKey}_buttons`).innerHTML =
            downloadLink + deleteLink;

        });
}

function controlDeleteFonts() {
    document.addEventListener("click", (event) => {
        if (event.target.matches(".delete-font")) {
            event.preventDefault(); // Evita que el navegador siga el enlace
            let fontKey = event.target.getAttribute("data-font");
            deleteFont(fontKey);
        }
    });
}
