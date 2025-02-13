import { heroicon } from "../heroicons";
import {
    controlsPagination,
    controlsSearch,
    updatePaginationInfo,
    updateArrayRecords,
    tabulatorBaseConfig,
    moreOptionsBtn,
    dropdownMenu,
} from "../tabulator_handler";
import { showModal, hideModal, showModalConfirmation } from "../modal_handler";
import {
    apiFetch,
    showElement,
    showFormErrors,
    resetFormErrors,
    getLiveSearchTomSelectInstance,
    updateInputImage,
    getMultipleFreeTomSelectInstance,
    getMultipleTomSelectInstance,
    setReadOnlyForSpecificFields,
    setDisabledSpecificFormFields,
    setDisabledSpecificDivFields,
    changeColorColoris,
    getMultipleFreeEmailsTomSelectInstance,
    updateInputFile,
    fillFormWithObject,
    debounce,
} from "../app";
import { TabulatorFull as Tabulator } from "tabulator-tables";
import { showToast } from "../toast.js";

let educationalProgramsTable;
let selectedEducationalPrograms = [];
let selectedEducationalProgramStudents = [];
let tomSelectCourses;
let tomSelectTags;
let tomSelectCategories;
let selectedEducationalProgramUid = null;
let educationalProgramStudentsTable;
let tomSelectContactEmails;

let tomSelectUsersToEnroll;

const endPointTable =
    "/learning_objects/educational_programs/get_educational_programs";

const endPointStudentTable =
    "/learning_objects/educational_programs/get_educational_program_students";

document.addEventListener("DOMContentLoaded", () => {
    initHandlers();
    initializeEducationalProgramsTable();

    initializeTomSelect();

    updateInputImage();
    controlValidationStudents();
    controlEnrollingDates();
    controlChecksSliders();
    updateInputFile();
});

function initHandlers() {
    document
        .getElementById("new-educational-program-btn")
        .addEventListener("click", () => {
            newEducationalProgram();
        });

    document
        .getElementById("educational-program-form")
        .addEventListener("submit", submitFormEducationalProgram);

    document
        .getElementById("btn-reload-table")
        .addEventListener("click", function () {
            reloadTable();
        });

    document
        .getElementById("btn-add-document")
        .addEventListener("click", function () {
            addDocument();
        });

    document
        .querySelector(".document-list")
        .addEventListener("click", removeDocument);

    document
        .getElementById("previsualize-slider")
        .addEventListener("click", function () {
            previsualizeSlider();
        });

    const changeStatusesBtn = document.getElementById("change-statuses-btn");

    if (changeStatusesBtn) {
        document
            .getElementById("change-statuses-btn")
            .addEventListener("click", function () {
                changeStatusesEducationalPrograms();
            });
    }

    document
        .getElementById("confirm-change-statuses-btn")
        .addEventListener("click", submitChangeStatusesEducationalPrograms);

    document
        .getElementById("enroll-students-btn")
        .addEventListener("click", function () {
            showModal("enroll-educational-program-modal");
            tomSelectUsersToEnroll = getLiveSearchTomSelectInstance(
                "#enroll_students",
                "/users/list_users/search_users_no_enrolled_educational_program/" +
                    selectedEducationalProgramUid +
                    "/",
                function (entry) {
                    return {
                        value: entry.uid,
                        text: `${entry.first_name} ${entry.last_name}`,
                    };
                }
            );
        });

    document
        .getElementById("enroll-btn")
        .addEventListener("click", function () {
            enrollStudentsToEducationalProgram();
        });

    document
        .getElementById("approve-students-btn")
        .addEventListener("click", function () {
            if (selectedEducationalProgramStudents.length) {
                showModalConfirmation(
                    "Aprobación de inscripciones",
                    "¿Estás seguro de que quieres aprobar las inscripciones de los estudiantes seleccionados?"
                ).then((result) => {
                    if (!result) return;
                    changeStatusStudentsEducationalProgram("ACCEPTED");
                });
            }
        });

    document
        .getElementById("reject-students-btn")
        .addEventListener("click", function () {
            if (selectedEducationalProgramStudents.length) {
                showModalConfirmation(
                    "Anulación de inscripciones",
                    "¿Estás seguro de que quieres rechazar las inscripciones de los estudiantes seleccionados?"
                ).then((result) => {
                    if (!result) return;
                    changeStatusStudentsEducationalProgram("REJECTED");
                });
            }
        });

    document
        .getElementById("delete-students-btn")
        .addEventListener("click", function () {
            if (selectedEducationalProgramStudents.length) {
                showModalConfirmation(
                    "Eliminación de inscripciones",
                    "¿Estás seguro de que quieres eliminar las inscripciones de los estudiantes seleccionados?"
                ).then((result) => {
                    if (!result) return;
                    deleteInscriptionsStudentsEducationalProgram();
                });
            }
        });

    document
        .getElementById("enroll-students-csv-btn")
        .addEventListener("click", function () {
            showModal("enroll-educational-program-csv-modal");
        });

    document
        .getElementById("enroll-educational-program-csv-btn")
        .addEventListener("click", function () {
            enrollStudentsCsv();
        });

    const generateTagsBtn = document.getElementById("generate-tags-btn");
    if (generateTagsBtn) {
        generateTagsBtn.addEventListener("click", () => {
            generateTags();
            generateTagsBtn.classList.add("hidden");
            setTimeout(() => {
                generateTagsBtn.classList.remove("hidden");
            }, 10000);
        });
    }

    document
        .getElementById("payment_mode")
        .addEventListener("change", function (e) {
            const paymentMode = e.target.value;
            updatePaymentMode(paymentMode);
        });

    document
        .getElementById("btn-add-payment")
        .addEventListener("click", function () {
            addPaymentTerm();
        });

    document
        .getElementById("payment-terms-list")
        .addEventListener("click", removePaymentTerm);

    document
        .getElementById("emit-credentials-students-btn")
        .addEventListener("click", function () {
            showModalConfirmation(
                "Emisión de credenciales",
                "¿Estás seguro de que quieres emitir las credenciales a los estudiantes seleccionados?"
            ).then((result) => {
                if (!result) return;
                actionCredentials("emit");
            });
        });

    document
        .getElementById("send-credentials-btn")
        .addEventListener("click", function () {
            showModalConfirmation(
                "Envío de credenciales",
                "¿Estás seguro de que quieres emitir las credenciales a los estudiantes seleccionados?"
            ).then((result) => {
                if (!result) return;
                actionCredentials("send");
            });
        });

    document
        .getElementById("seal-credentials-btn")
        .addEventListener("click", function () {
            showModalConfirmation(
                "Sellado de credenciales",
                "¿Estás seguro de que quieres sellar las credenciales a los estudiantes seleccionados?"
            ).then((result) => {
                if (!result) return;
                actionCredentials("seal");
            });
        });
}

function actionCredentials(action) {
    let url = "";
    if (action == "emit")
        url = "/learning_objects/educational_programs/emit_credentials";
    else if (action == "send")
        url = "/learning_objects/educational_programs/send_credentials";
    else if (action == "seal")
        url = "/learning_objects/educational_programs/seal_credentials";
    else return;

    const studentsUids = selectedEducationalProgramStudents.map(
        (student) => student.uid
    );

    const params = {
        url,
        method: "POST",
        loader: true,
        body: {
            educational_program_uid: selectedEducationalProgramUid,
            students_uids: studentsUids,
        },
        stringify: true,
        toast: true,
    };

    apiFetch(params).then(() => {
        loadEducationalProgramsStudentsModal(selectedEducationalProgramUid);
    });
}

/**
 * Muestra u oculta el área de criterios de evaluación y el selector de documentos
 * en función de si el curso tiene validación de estudiantes.
 */
function controlValidationStudents() {
    const checkbox = document.getElementById("validate_student_registrations");

    const documentsContainer = document.getElementById("documents-container");
    const criteriaArea = document.getElementById("criteria-area");

    checkbox.addEventListener("change", function () {
        if (checkbox.checked) {
            showArea(documentsContainer, true);
            showArea(criteriaArea, true);
        } else {
            showArea(documentsContainer, false);
            showArea(criteriaArea, false);
        }
    });
}

function initializeTomSelect() {
    tomSelectCourses = getLiveSearchTomSelectInstance(
        "#courses",
        "/learning_objects/educational_programs/search_courses_without_educational_program/",
        function (entry) {
            return {
                value: entry.uid,
                text: entry.title,
            };
        }
    );

    tomSelectTags = getMultipleFreeTomSelectInstance("#tags");
    tomSelectCategories = getMultipleTomSelectInstance("#select-categories");

    const debouncedUpdateMedianInscriptionsLabel = debounce(
        updateMedianInscriptionsLabel,
        1000
    );
    tomSelectCategories.on("change", function (value) {
        debouncedUpdateMedianInscriptionsLabel(value);
    });

    tomSelectContactEmails =
        getMultipleFreeEmailsTomSelectInstance("#contact_emails");
}

/**
 * Configuración de la tabla de llamadas utilizando la biblioteca Tabulator.
 * Define las columnas, obtiene los datos del endpoint y maneja la paginación.
 */
function initializeEducationalProgramsTable() {
    const columns = [
        {
            title: '<div class="checkbox-cell"><input type="checkbox" id="select-all-checkbox"/></div>',
            headerClick: function (e) {
                const selectAllCheckbox = e.target;
                if (selectAllCheckbox.type === "checkbox") {
                    // Asegúrate de que el clic fue en el checkbox
                    educationalProgramsTable.getRows().forEach((row) => {
                        const cell = row.getCell("select");
                        const checkbox = cell
                            .getElement()
                            .querySelector('input[type="checkbox"]');
                        checkbox.checked = selectAllCheckbox.checked;
                        selectedEducationalPrograms = updateArrayRecords(
                            checkbox,
                            row.getData(),
                            selectedEducationalPrograms
                        );
                    });
                }
            },
            field: "select",
            formatter: function (cell, formatterParams, onRendered) {
                const uid = cell.getRow().getData().uid;
                return `<input type="checkbox" data-uid="${uid}"/>`;
            },
            cssClass: "checkbox-cell",
            cellClick: function (e, cell) {
                // Lógica cuando se hace clic en la celda
                const checkbox = e.target;

                selectedEducationalPrograms = updateArrayRecords(
                    checkbox,
                    cell.getRow().getData(),
                    selectedEducationalPrograms
                );
            },
            width: 60,
            headerSort: false,
        },
        { title: "Identificador", field: "identifier", widthGrow: 1 },
        { title: "Nombre", field: "name", widthGrow: 3 },
        {
            title: "Estado",
            field: "status_name",
            formatter: function (cell, formatterParams, onRendered) {
                const color = getStatusEducationalProgramColor(
                    cell.getRow().getData().status_code
                );
                return `
                <div class="label-status" style="background-color:${color}">${
                    cell.getRow().getData().status_name
                        ? cell.getRow().getData().status_name
                        : ""
                }</div>
                `;
            },
            widthGrow: 2,
        },
        {
            title: "Tipo de programa formativo",
            field: "educational_program_type_name",
            widthGrow: 2,
        },
        {
            title: "Convocatoria",
            field: "call_name",
            widthGrow: 2,
        },
        {
            title: "",
            field: "actions",
            formatter: function (cell, formatterParams, onRendered) {
                return `<button type="button" title="Editar" class='btn action-btn'>${heroicon(
                    "pencil-square",
                    "outline"
                )}</button>`;
            },
            cellClick: function (e, cell) {
                e.preventDefault();
                const educationalProgramClicked = cell.getRow().getData();
                loadEducationalProgramModal(educationalProgramClicked.uid);
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
        {
            title: "",
            field: "actions",
            formatter: function (cell, formatterParams, onRendered) {
                return `
                    <button type="button" title="Más opciones" class='btn action-btn'>${heroicon(
                        "ellipsis-horizontal",
                        "outline"
                    )}</button>
                `;
            },
            cellClick: function (e, cell) {
                e.preventDefault();
                const btnArray = [
                    {
                        icon: "user-group",
                        type: "outline",
                        tooltip: "Listado de alumnos del curso",
                        action: (educationalprogram) => {
                            loadEducationalProgramsStudentsModal(
                                educationalprogram.uid,
                                educationalprogram.validate_student_registrations
                            );
                        },
                    },
                    {
                        icon: "document-duplicate",
                        type: "outline",
                        tooltip: "Duplicar programa formativo",
                        action: (educational_program) => {
                            handleDuplicationEducationalProgram(
                                educational_program.uid
                            );
                        },
                    },
                    {
                        icon: "academic-cap",
                        type: "outline",
                        tooltip:
                            "Emitir todas las credenciales del programa formativo",
                        action: (educational_program) => {
                            showModalConfirmation(
                                "Emisión de todas las credenciales",
                                "¿Estás seguro de que quieres emitir las credenciales a los estudiantes del programa seleccionado?"
                            ).then((result) => {
                                if (result) {
                                    emitAllCredentialsEducationalProgram(
                                        educational_program.uid
                                    );
                                }
                            });
                        },
                    },
                ];

                setTimeout(() => {
                    moreOptionsBtn(cell, btnArray);
                }, 1);
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
    ];

    educationalProgramsTable = new Tabulator("#educational-programs-table", {
        ajaxURL: endPointTable,
        ...tabulatorBaseConfig,
        rowContextMenu: [
            {
                label: `${heroicon("pencil-square")} Editar`,
                action: function (e, column) {
                    const educationalProgramClicked = column.getData();
                    loadEducationalProgramModal(educationalProgramClicked.uid);
                },
            },
            {
                label: `${heroicon("user-group")} Ver alumnos del programa`,
                action: function (e, column) {
                    const educationalProgramClicked = column.getData();
                    loadEducationalProgramsStudentsModal(
                        educationalProgramClicked.uid,
                        educationalProgramClicked.validate_student_registrations
                    );
                },
            },
            {
                label: `${heroicon(
                    "document-duplicate"
                )} Duplicar programa formativo`,
                action: function (e, column) {
                    const educationalProgramClicked = column.getData();
                    handleDuplicationEducationalProgram(
                        educationalProgramClicked.uid
                    );
                },
            },
            {
                label: `${heroicon("folder-plus")} Crear nueva edición`,
                action: function (e, column) {
                    const educationalProgramClicked = column.getData();
                    handleNewEditionEducationalProgram(
                        educationalProgramClicked.uid
                    );
                },
            },
            {
                label: `${heroicon(
                    "document-arrow-up"
                )} Emisión de todas las credenciales`,
                action: function (e, column) {
                    showModalConfirmation(
                        "Emisión de todas las credenciales",
                        "¿Estás seguro de que quieres emitir las credenciales a los estudiantes del programa seleccionado?"
                    ).then((result) => {
                        const educationalProgramClicked = column.getData();
                        if (result)
                            emitAllCredentialsEducationalProgram(
                                educationalProgramClicked.uid
                            );
                    });
                },
            },
        ],
        ajaxResponse: function (url, params, response) {
            updatePaginationInfo(
                educationalProgramsTable,
                response,
                "educational-resource-types-table"
            );
            selectedEducationalPrograms = [];
            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });

    controlsSearch(
        educationalProgramsTable,
        endPointTable,
        "educational-resource-types-table"
    );

    controlsPagination(
        educationalProgramsTable,
        "educational-resource-types-table"
    );
}

function emitAllCredentialsEducationalProgram(educationalProgramUid) {
    const params = {
        url: `/learning_objects/educational_programs/emit_all_credentials`,
        method: "POST",
        stringify: true,
        body: {
            educational_program_uid: educationalProgramUid,
        },
        toast: true,
        loader: true,
    };

    apiFetch(params);
}

async function loadEducationalProgramModal(educationalProgramUid) {
    const params = {
        url: `/learning_objects/educational_programs/get_educational_program/${educationalProgramUid}`,
        method: "GET",
        loader: true,
    };

    apiFetch(params).then((data) => {
        resetModal();
        fillEducationalProgramModal(data);
        showModal("educational-program-modal", "Editar programa formativo");
    });
}

function fillEducationalProgramModal(educationalProgram) {
    document.getElementById("educational_program_uid").value =
        educationalProgram.uid;

    fillFormWithObject(educationalProgram, "educational-program-form");

    document.getElementById("validate_student_registrations").value =
        educationalProgram.validate_student_registrations;

    document.getElementById("validate_student_registrations").checked =
        educationalProgram.validate_student_registrations;

    const featuredSliderColorFontElement = document.getElementById(
        "featured_slider_color_font"
    );
    changeColorColoris(
        featuredSliderColorFontElement,
        educationalProgram.featured_slider_color_font
    );

    if (educationalProgram.validate_student_registrations) {
        let criteriaArea = document.getElementById("criteria-area");
        showArea(criteriaArea, true);

        let documentsContainer = document.getElementById("documents-container");
        showArea(documentsContainer, true);
    }

    if (
        educationalProgram.validate_student_registrations ||
        (educationalProgram && educationalProgram.cost > 0)
    ) {
        const enrollingDates = document.getElementById(
            "enrolling-dates-container"
        );
        showArea(enrollingDates, true);
    }

    loadDocuments(educationalProgram.educational_program_documents);

    showBigSliderInfo(educationalProgram.featured_slider);

    if (educationalProgram.tags) {
        educationalProgram.tags.forEach((tag) => {
            tomSelectTags.addOption({ value: tag.tag, text: tag.tag });
            tomSelectTags.addItem(tag.tag);
        });
    }

    if (educationalProgram.image_path) {
        document.getElementById("image_path_preview").src =
            "/" + educationalProgram.image_path;
    } else {
        document.getElementById("image_path_preview").src = defaultImagePreview;
    }

    if (educationalProgram.courses) {
        educationalProgram.courses.forEach((course) => {
            const option = {
                value: course.uid,
                text: course.title,
            };
            tomSelectCourses.addOption(option);
            tomSelectCourses.addItem(option.value);
        });
    }

    if (educationalProgram.categories) {
        educationalProgram.categories.forEach((category) => {
            tomSelectCategories.addOption({
                value: category.uid,
                text: category.name,
            });
            tomSelectCategories.addItem(category.uid);
        });
    }

    if (educationalProgram.featured_slider_image_path) {
        document.getElementById("featured_slider_image_path_preview").src =
            "/" + educationalProgram.featured_slider_image_path;
    }

    document.getElementById("featured_slider").value =
        educationalProgram.featured_slider;
    document.getElementById("featured_slider").checked =
        educationalProgram.featured_slider;

    document.getElementById("featured_main_carrousel").value =
        educationalProgram.featured_main_carrousel;
    document.getElementById("featured_main_carrousel").checked =
        educationalProgram.featured_main_carrousel;

    if (educationalProgram.contact_emails) {
        educationalProgram.contact_emails.forEach((contact_email) => {
            tomSelectContactEmails.addOption({
                value: contact_email.email,
                text: contact_email.email,
            });
            tomSelectContactEmails.addItem(contact_email.email);
        });
    }

    if (educationalProgram.status.code === "INTRODUCTION") {
        document
            .getElementById("draft-button-container")
            .classList.remove("hidden");
    }

    updatePaymentMode(educationalProgram.payment_mode);

    if (educationalProgram.payment_mode === "INSTALLMENT_PAYMENT") {
        loadPaymentTerms(educationalProgram.payment_terms);
    }

    if (window.rolesUser.includes("MANAGEMENT")) {
        toggleFieldAccessibility(true);
    } else if (educationalProgram.status.code === "INTRODUCTION") {
        toggleFieldAccessibility(true);
        if (educationalProgram.educational_program_origin_uid) {
            setFieldsEdition();
        }
    } else if (
        !["UNDER_CORRECTION_APPROVAL", "UNDER_CORRECTION_PUBLICATION"].includes(
            educationalProgram.status.code
        )
    ) {
        toggleFieldAccessibility(false);
    } else {
        toggleFieldAccessibility(true);
    }
}

function setFieldsEdition() {
    // Ponemos readonly estos campos
    const idsReadOnly = [
        "name",
        "description",
        "featured_slider_title",
        "featured_slider_description",
    ];
    setReadOnlyForSpecificFields("educational-program-form", idsReadOnly, true);

    const idsDisable = [
        "educational_program_type_uid",
        "featured_slider_color_font",
        "featured_slider_image_path",
    ];
    setDisabledSpecificFormFields("educational-program-form", idsDisable, true);

    tomSelectCourses.disable();
    tomSelectTags.disable();
    tomSelectCategories.disable();
}

function toggleFieldAccessibility(shouldEnable) {
    const fieldsToToggleReadOnly = [
        "name",
        "description",
        "min_required_students",
        "inscription_start_date",
        "inscription_finish_date",
        "evaluation_criteria",
        "cost",
        "enrolling_start_date",
        "enrolling_finish_date",
        "realization_start_date",
        "realization_finish_date",
        "featured_slider_title",
        "featured_slider_description",
        "featured_slider_color_font",
    ];

    // Cambia la propiedad readOnly de los campos especificados
    setReadOnlyForSpecificFields(
        "educational-program-form",
        fieldsToToggleReadOnly,
        !shouldEnable
    );

    const fieldsToToggleDisabled = [
        "educational_program_type_uid",
        "call_uid",
        "payment_mode",
        "featured_slider",
        "featured_main_carrousel",
        "featured_slider_color_font",
        "featured_slider_image_path",
        "validate_student_registrations",
        "image_path",
    ];

    // Cambia la propiedad disabled de los campos especificados
    setDisabledSpecificFormFields(
        "educational-program-form",
        fieldsToToggleDisabled,
        !shouldEnable
    );

    // Cambia la propiedad disabled de divs específicos
    const divsToToggleDisabled = [
        "document-container",
        "payment_terms",
        "btns-save",
    ];
    setDisabledSpecificDivFields(divsToToggleDisabled, !shouldEnable);

    // Habilita o deshabilita los selectores TomSelect
    shouldEnable ? tomSelectCourses.enable() : tomSelectCourses.disable();
    shouldEnable ? tomSelectTags.enable() : tomSelectTags.disable();
    shouldEnable ? tomSelectCategories.enable() : tomSelectCategories.disable();
    shouldEnable
        ? tomSelectContactEmails.enable()
        : tomSelectContactEmails.disable();
}

/**
 * Muestra u oculta el contenedor de fechas de matriculación en
 * función del coste del programa o de la validación de matrículas.
 */
function controlEnrollingDates() {
    const costInput = document.getElementById("cost");
    const checkboxValidateStudents = document.getElementById(
        "validate_student_registrations"
    );
    const enrollingDates = document.getElementById("enrolling-dates-container");

    function checkConditions() {
        const shouldShow =
            costInput.value > 0 || checkboxValidateStudents.checked;
        showElement(enrollingDates, shouldShow);
    }

    costInput.addEventListener("change", checkConditions);
    checkboxValidateStudents.addEventListener("change", checkConditions);
}

/**
 * Este bloque maneja la presentación del formulario para una nueva llamada.
 * Recopila los datos y los envía a un endpoint específico.
 * Si la operación tiene éxito, actualiza la tabla y muestra un toast.
}
 */
function submitFormEducationalProgram() {
    const action = event.submitter.value;

    // Habilitar todos los campos para que se envíen en el formulario
    const form = document.getElementById("educational-program-form");
    const disabledFields = form.querySelectorAll(":disabled");
    disabledFields.forEach(function (field) {
        field.disabled = false;
    });

    const formData = new FormData(this);

    disabledFields.forEach(function (field) {
        field.disabled = true;
    });

    formData.append("action", action);

    const checkboxValidateStudentRegistrations = form.querySelector(
        "#validate_student_registrations"
    );
    formData.append(
        "validate_student_registrations",
        checkboxValidateStudentRegistrations.checked ? "1" : "0"
    );

    const tags = tomSelectTags.items;
    formData.append("tags", JSON.stringify(tags));

    const categories = tomSelectCategories.items;
    formData.append("categories", JSON.stringify(categories));

    const documents = getDocuments();
    formData.append("documents", JSON.stringify(documents));

    const checkboxFeaturedBigCarrousel = form.querySelector("#featured_slider");
    formData.append(
        "featured_slider",
        checkboxFeaturedBigCarrousel.checked ? "1" : "0"
    );

    const checkboxFeaturedSmallCarrousel = form.querySelector(
        "#featured_main_carrousel"
    );
    formData.append(
        "featured_main_carrousel",
        checkboxFeaturedSmallCarrousel.checked ? "1" : "0"
    );

    formData.append(
        "featured_slider_color_font",
        document.getElementById("featured_slider_color_font").value
    );

    const contactEmails = tomSelectContactEmails.items;
    formData.append("contact_emails", JSON.stringify(contactEmails));

    const paymentTerms = getPaymentTerms();
    formData.append("payment_terms", JSON.stringify(paymentTerms));

    const featuredImagePath = document.getElementById(
        "featured_slider_image_path"
    ).files[0];
    formData.append("featured_slider_image_path", featuredImagePath ?? "");

    const params = {
        url: "/learning_objects/educational_programs/save_educational_program",
        method: "POST",
        body: formData,
        toast: true,
        loader: true,
    };

    resetFormErrors("educational-program-form");

    apiFetch(params)
        .then(() => {
            hideModal("educational-program-modal");
            reloadTable();
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

function newEducationalProgram() {
    const draftButtonContainer = document.getElementById(
        "draft-button-container"
    );
    draftButtonContainer.classList.remove("hidden");
    resetModal();
    toggleFieldAccessibility(true);
    showModal("educational-program-modal", "Crear programa formativo");
    showBigSliderInfo(false);
}

function resetModal() {
    document.getElementById("educational-program-form").reset();
    document.getElementById("educational_program_uid").value = "";
    resetFormErrors();

    const colorisSelector = document.getElementById(
        "featured_slider_color_font"
    );
    colorisSelector.value = "";
    changeColorColoris(colorisSelector, "");

    let documentsContainer = document.getElementById("documents-container");
    showArea(documentsContainer, false);

    document.getElementById("featured_slider_image_path_preview").src =
        defaultImagePreview;

    tomSelectCourses.clear();
    tomSelectCourses.clearOptions();
    tomSelectTags.clear();
    tomSelectTags.clearOptions();
    tomSelectCategories.clear();
    tomSelectContactEmails.clear();

    document.getElementById("document-list").innerHTML = "";
    const enrollingDates = document.getElementById("enrolling-dates-container");
    showArea(enrollingDates, false);

    let criteriaArea = document.getElementById("criteria-area");
    showArea(criteriaArea, false);

    document.getElementById("payment-terms-list").innerHTML = "";
    updatePaymentMode("SINGLE_PAYMENT");
}

function reloadTable() {
    educationalProgramsTable.setData(endPointTable);
}

function addDocument() {
    const template = document
        .getElementById("document-template")
        .content.cloneNode(true);
    document.getElementById("document-list").appendChild(template);
}

function updateMedianInscriptionsLabel(value) {
    const categoriesMedianInscriptionsLabel = document.getElementById(
        "categories-median-inscriptions"
    );

    if (!value.length) {
        categoriesMedianInscriptionsLabel.innerText =
            "Seleccione una o varias categorías para calcular la mediana de inscripciones";
        return;
    }

    const params = {
        url: `/learning_objects/educational_programs/calculate_median_enrollings_categories`,
        method: "POST",
        stringify: true,
        body: {
            categories_uids: value,
        },
    };

    apiFetch(params).then((data) => {
        categoriesMedianInscriptionsLabel.innerText =
            "Se estima una mediana de inscripciones de " +
            data.median +
            " estudiante" +
            (data.median !== 1 ? "s" : "");
    });
}

function removeDocument(event) {
    let target = event.target;

    if (!target.classList.contains("btn-remove-document")) {
        target = target.closest(".btn-remove-document");
    }

    if (target) {
        target.closest(".document").remove();
    }
}

function getDocuments() {
    const courseDocuments = document
        .getElementById("document-list")
        .querySelectorAll(".document");
    const documentsData = [];

    courseDocuments.forEach((courseDocument) => {
        const uid = courseDocument.dataset.documentUid;
        const documentName =
            courseDocument.querySelector(".document-name").value;
        documentsData.push({ uid, documentName });
    });

    return documentsData;
}

function loadDocuments(courseDocuments) {
    // Limpiar el contenedor de documentos
    const containerDocuments = document.getElementById("document-list");
    containerDocuments.innerHTML = "";

    // Añadir cada documento al contenedor
    courseDocuments.forEach((courseDocument) => {
        const documentTemplate = document
            .getElementById("document-template")
            .content.cloneNode(true);

        documentTemplate.querySelector(".document").dataset.documentUid =
            courseDocument.uid;
        documentTemplate.querySelector(".document-name").value =
            courseDocument.document_name;
        containerDocuments.appendChild(documentTemplate);
    });
}

function controlChecksSliders() {
    const checkbox = document.getElementById("featured_slider");

    checkbox.addEventListener("change", function () {
        if (checkbox.checked) showBigSliderInfo(true);
        else showBigSliderInfo(false);
    });
}

function showBigSliderInfo(show) {
    const bigCarrouselInfoSection = document.getElementById(
        "featured-slider-info"
    );

    if (show) {
        bigCarrouselInfoSection.classList.remove("hidden");
        bigCarrouselInfoSection.classList.add("block");
    } else {
        bigCarrouselInfoSection.classList.remove("block");
        bigCarrouselInfoSection.classList.add("hidden");
    }
}

/**
 * Esta función se utiliza para previsualizar el slider del curso antes de guardarlo.
 * Recoge el archivo de imagen y los valores de los campos de título, descripción y color,
 * y posteriormente redirecciona al front pasándole por parámetro el uid de previsualización.
 */
function previsualizeSlider() {
    let fileInput = document.getElementById("featured_slider_image_path");
    let file = fileInput.files[0];

    let formData = new FormData();
    formData.append("image", file ?? "");
    formData.append(
        "title",
        document.getElementById("featured_slider_title").value
    );
    formData.append(
        "description",
        document.getElementById("featured_slider_description").value
    );
    formData.append(
        "color",
        document.getElementById("featured_slider_color_font").value
    );

    formData.append("learning_object_type", "educational_program");
    formData.append(
        "educational_program_uid",
        document.getElementById("educational_program_uid").value
    );

    const params = {
        url: "/sliders/save_previsualization",
        method: "POST",
        body: formData,
        toast: true,
        stringify: false,
        loader: true,
    };

    apiFetch(params).then((data) => {
        // Abrir en nueva pestaña
        let previsualizationUrl = `${window.frontUrl}?previsualize-slider=${data.previsualizationUid}`;
        window.open(previsualizationUrl, "_blank");
    });
}
/**
 *
 * @param {*} statusCode
 * @returns Color de fondo que le coresponde a la etiqueta del estado
 */
function getStatusEducationalProgramColor(statusCode) {
    const statusColors = {
        INTRODUCTION: "#EBEBF4",
        PENDING_APPROVAL: "#F0F4EB",
        ACCEPTED: "#EBF3F4",
        REJECTED: "#F4EBF0",
        UNDER_CORRECTION_APPROVAL: "#F0F4EB",
        PENDING_PUBLICATION: "#F4EFEB",
        ACCEPTED_PUBLICATION: "#F4F3EB",
        UNDER_CORRECTION_PUBLICATION: "#F4EBEB",
        INSCRIPTION: "#EBEFF4",
        PENDING_INSCRIPTION: "#FBEDED",
        ENROLLING: "#F3F4FF",
        DEVELOPMENT: "#EDF4FB",
        FINISHED: "#FBF4ED",
        RETIRED: "#FDF5FE",
    };

    return statusColors[statusCode];
}

/**
 * Configura el evento de clic para cambiar los estados de los cursos seleccionados.
 * Muestra un modal para que el usuario seleccione el nuevo estado y proporcione un motivo.
 */
function changeStatusesEducationalPrograms() {
    if (!selectedEducationalPrograms.length) {
        showToast("No has seleccionado ningún curso", "error");

        return;
    }

    let educationalProgramsList = document.getElementById(
        "educational-programs-list"
    );
    educationalProgramsList.innerHTML = "";

    selectedEducationalPrograms.forEach((edu) => {
        let optionsStatuses = [];

        if (
            [
                "PENDING_APPROVAL",
                "ACCEPTED",
                "REJECTED",
                "UNDER_CORRECTION_APPROVAL",
                "PENDING_PUBLICATION",
                "UNDER_CORRECTION_PUBLICATION",
                "ACCEPTED_PUBLICATION",
            ].includes(edu.status_code)
        ) {
            optionsStatuses = [
                {
                    label: "Aceptado",
                    value: "ACCEPTED",
                },
                {
                    label: "Rechazado",
                    value: "REJECTED",
                },
                {
                    label: "En subsanación para aprobación",
                    value: "UNDER_CORRECTION_APPROVAL",
                },
                {
                    label: "Pendiente de publicación",
                    value: "PENDING_PUBLICATION",
                },
                {
                    label: "En subsanación para publicación",
                    value: "UNDER_CORRECTION_PUBLICATION",
                },
                {
                    label: "Aceptado para publicación",
                    value: "ACCEPTED_PUBLICATION",
                },
            ];

            // Excluímos el estado actual
            optionsStatuses = optionsStatuses.filter(
                (option) => option.value !== status
            );
        } else if (edu.status_code === "PENDING_DECISION") {
            optionsStatuses = [
                {
                    label: "En inscripción",
                    value: "INSCRIPTION",
                },
            ];
        }

        // Se podrá retirar un curso en cualquier estado
        optionsStatuses.push({
            label: "Retirado",
            value: "RETIRED",
        });

        // Clonamos la plantilla de cambio de estado de curso
        let statusEducationalProgramTemplate = document
            .getElementById("change-status-educational-program-template")
            .content.cloneNode(true);

        statusEducationalProgramTemplate.querySelector(
            ".change-status-educational-program .educational-program-name"
        ).innerText = edu.name;
        statusEducationalProgramTemplate.querySelector(
            ".change-status-educational-program .educational-program"
        ).dataset.uid = edu.uid;

        // Cargamos los estados
        let selectElement = statusEducationalProgramTemplate.querySelector(
            ".change-status-educational-program .status-educational-program"
        );
        optionsStatuses.forEach((option) => {
            let optionElement = document.createElement("option");
            optionElement.value = option.value;
            optionElement.text = option.label;

            selectElement.add(optionElement);
        });

        educationalProgramsList.appendChild(statusEducationalProgramTemplate);
    });

    document.getElementById("bulk_change_status").value = "";

    bulkChangeStatuses();
    showModal(
        "change-statuses-educational-programs-modal",
        "Cambiar estado de programas formativos"
    );
}

/**
 * cambia el estado de todos los selectores en los cursos en el modal cambio de estado
 */
function bulkChangeStatuses() {
    const bulkSelect = document.getElementById("bulk_change_status");
    const selectors = document.querySelectorAll(
        "#educational-programs-list .status-educational-program"
    );
    bulkSelect.addEventListener("change", function () {
        selectors.forEach((select) => {
            const opcionExist = select.querySelector(
                'option[value="' + bulkSelect.value + '"]'
            );
            if (opcionExist) {
                opcionExist.selected = true;
            }
        });
    });
}

function getEducationalProgramsStatuses() {
    const educationalProgramContainer = document.getElementById(
        "educational-programs-list"
    );
    const EducationalProgramDivs = educationalProgramContainer.querySelectorAll(
        "div.educational-program"
    );
    const changesEducationalProgramsStatuses = [];

    EducationalProgramDivs.forEach((courseElement) => {
        const uid = courseElement.getAttribute("data-uid");
        const statusElement = courseElement.querySelector(
            ".status-educational-program"
        );
        const reasonElement = courseElement.querySelector(
            ".reason-status-educational-program"
        );

        const status = statusElement.value;
        const reason = reasonElement.value;

        changesEducationalProgramsStatuses.push({
            uid,
            status,
            reason,
        });
    });

    return changesEducationalProgramsStatuses;
}

/**
 * Envía los cambios de estado de los cursos seleccionados.
 * Recoge los nuevos estados y los motivos de cambio de cada curso y realiza una petición 'fetch' para actualizarlos.
 */
function submitChangeStatusesEducationalPrograms() {
    const changesEducationalProgramsStatuses = getEducationalProgramsStatuses();

    const params = {
        url: "/learning_objects/educational_programs/change_statuses_educational_programs",
        method: "POST",
        body: { changesEducationalProgramsStatuses },
        toast: true,
        loader: true,
        stringify: true,
    };

    apiFetch(params)
        .then(() => {
            hideModal("change-statuses-educational-programs-modal");
            reloadTableCourses();
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

function reloadTableCourses() {
    educationalProgramsTable.setData(endPointTable);
}

/**
 * Carga y muestra el modal para la gestión de estudiantes de un curso.
 * Inicializa la tabla de estudiantes del curso y configura los eventos para la búsqueda y eliminación de estudiantes.
 */
function loadEducationalProgramsStudentsModal(
    educationalProgramUid,
    validateStudentRegistrations
) {
    if (
        educationalProgramStudentsTable != null &&
        educationalProgramStudentsTable != undefined
    ) {
        educationalProgramStudentsTable.destroy();
    }

    selectedEducationalProgramUid = educationalProgramUid;

    // Mostrar u ocultar botones de validación de estudiantes
    const validateStudentRegistrationsButtons = [
        "approve-students-btn",
        "reject-students-btn",
    ];

    validateStudentRegistrationsButtons.forEach((button) => {
        const btn = document.getElementById(button);

        if (validateStudentRegistrations) btn.classList.remove("hidden");
        else btn.classList.add("hidden");
    });

    initializeEducationalProgramStudentsTable(
        educationalProgramUid,
        validateStudentRegistrations
    );

    controlsPagination(
        educationalProgramStudentsTable,
        "educational-program-students-table"
    );

    showModal("educational-program-students-modal", "Listado de alumnos");
}

/**
 * Inicializa la tabla de estudiantes de un curso específico.
 * Configura las columnas y las opciones de ajax para la tabla de estudiantes usando 'Tabulator'.
 */
function initializeEducationalProgramStudentsTable(
    educationalProgramUid,
    validateStudentRegistrations
) {
    const columns = [
        {
            title: '<div class="checkbox-cell"><input type="checkbox" id="select-all-checkbox"/></div>',
            field: "select",
            formatter: function (cell, formatterParams, onRendered) {
                const uid = cell.getRow().getData().uid;
                return `<input type="checkbox" data-uid="${uid}"/>`;
            },
            cssClass: "checkbox-cell",
            headerClick: function (e) {
                selectedEducationalProgramStudents = handleHeaderClick(
                    educationalProgramStudentsTable,
                    e,
                    selectedEducationalProgramStudents
                );
            },
            cellClick: function (e, cell) {
                // Lógica cuando se hace clic en la celda
                const checkbox = e.target;
                const data = cell.getRow().getData();

                selectedEducationalProgramStudents = updateArrayRecords(
                    checkbox,
                    data,
                    selectedEducationalProgramStudents
                );
            },
            headerSort: false,
            width: 60,
        },
        { title: "Nombre", field: "first_name", widthGrow: "2" },
        { title: "Apellidos", field: "last_name", widthGrow: "3" },
        { title: "NIF", field: "nif", widthGrow: "2" },
        {
            title: "Estado de la credencial",
            field: "emissions_block_uuid",
            formatter: function (cell, formatterParams, onRendered) {
                const credential = cell.getRow().getData()
                    .educational_program_student_info.emissions_block_uuid;
                return credential ? "Emitida" : "No emitida";
            },
            cellClick: function (e, cell) {},
            widthGrow: 3,
            resizable: false,
        },
        {
            title: "Credencial enviada",
            field: "credential_sent",
            formatter: function (cell, formatterParams, onRendered) {
                const credentialSent = cell.getRow().getData()
                    .educational_program_student_info.credential_sent;
                return credentialSent ? "Sí" : "No";
            },
            cellClick: function (e, cell) {},
            widthGrow: 3,
            resizable: false,
        },
        {
            title: "Credencial sellada",
            field: "credential_sealed",
            formatter: function (cell, formatterParams, onRendered) {
                const credentialSealed = cell.getRow().getData()
                    .educational_program_student_info.credential_sealed;
                return credentialSealed ? "Sí" : "No";
            },
            cellClick: function (e, cell) {},
            widthGrow: 3,
            resizable: false,
        },
    ];

    // Si el programa formativo requiere validación de matrículas, se añaden las columnas de aprobación y documentos
    if (validateStudentRegistrations) {
        columns.push(
            {
                title: "Aprobado",
                field: "educational_programs_students.approved",
                formatter: function (cell, formatterParams, onRendered) {
                    const rowData = cell.getRow().getData();

                    const approved =
                        rowData.educational_program_student_info
                            .acceptance_status;

                    if (approved == "PENDING") return "Pendiente de decisión";
                    else if (approved == "ACCEPTED") return "Si";
                    else return "No";
                },
                widthGrow: "3",
                resizable: false,
            },
            {
                title: "Documentos presentados",
                field: "actions",
                formatter: function (cell, formatterParams, onRendered) {
                    const rowData = cell.getRow().getData();
                    if (rowData.educational_program_documents.length) {
                        return `
                        <button class="dropdown-button" type="button" id="menu-button" aria-expanded="true" aria-haspopup="true">
                            Descarga
                            ${heroicon("chevron-down", "outline")}
                        </button>
                    </div>
                        `;
                    } else {
                        return "";
                    }
                },
                cellClick: function (e, cell) {
                    e.preventDefault();
                    const rowData = cell.getRow().getData();

                    if (rowData.educational_program_documents) {
                        const btnArray = [];
                        rowData.educational_program_documents.forEach(
                            (document) => {
                                btnArray.push({
                                    text: document.educational_program_document
                                        .document_name,
                                    action: () => {
                                        downloadDocument(document.uid);
                                    },
                                });
                            }
                        );

                        setTimeout(() => {
                            dropdownMenu(cell, btnArray);
                        }, 1);
                    }
                },
                cssClass: "text-center",
                headerSort: false,
                widthGrow: 3,
                resizable: false,
            }
        );
    }

    educationalProgramStudentsTable = new Tabulator(
        "#educational-program-students-table",
        {
            ajaxURL: `${endPointStudentTable}/${educationalProgramUid}`,
            ajaxConfig: "GET",
            ...tabulatorBaseConfig,

            ajaxResponse: function (url, params, response) {
                selectedEducationalProgramStudents = [];
                return {
                    last_page: response.last_page,
                    data: response.data,
                };
            },
            columns: columns,
        }
    );

    controlsPagination(
        educationalProgramStudentsTable,
        "educational-program-students-table"
    );

    controlsSearch(
        educationalProgramStudentsTable,
        `${endPointStudentTable}/${educationalProgramUid}`,
        "educational-program-students-table"
    );
}

function enrollStudentsToEducationalProgram() {
    const usersToEnroll = tomSelectUsersToEnroll.getValue();

    const formData = new FormData();

    formData.append("EducationalProgramUid", selectedEducationalProgramUid);
    usersToEnroll.forEach((user) => {
        formData.append("usersToEnroll[]", user);
    });

    const params = {
        url: "/learning_objects/educational_program/enroll_students",
        method: "POST",
        body: formData,
        loader: true,
        toast: true,
    };

    apiFetch(params)
        .then(() => {
            hideModal("enroll-educational-program-modal");
            tomSelectUsersToEnroll.destroy();
            tomSelectUsersToEnroll = getLiveSearchTomSelectInstance(
                "#enroll_students",
                "/users/list_users/search_users_no_enrolled_educational_program/" +
                    selectedEducationalProgramUid +
                    "/",
                function (entry) {
                    return {
                        value: entry.uid,
                        text: `${entry.first_name} ${entry.last_name}`,
                    };
                }
            );
            reloadStudentsTable();
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

function reloadStudentsTable() {
    const endpoint = `${endPointStudentTable}/${selectedEducationalProgramUid}`;
    educationalProgramStudentsTable.setData(endpoint);
}

function changeStatusStudentsEducationalProgram(status) {
    const uidsStudentsInscriptions = getUidsStudentsInscriptions();

    const params = {
        url: "/learning_objects/educational_program/change_status_inscriptions_educational_program",
        method: "POST",
        body: { uids: uidsStudentsInscriptions, status: status },
        toast: true,
        loader: true,
        stringify: true,
    };

    apiFetch(params)
        .then(() => {
            reloadStudentsTable();
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

function deleteInscriptionsStudentsEducationalProgram() {
    const uidsStudentsInscriptions = getUidsStudentsInscriptions();

    const params = {
        url: "/learning_objects/educational_program/delete_inscriptions_educational_program",
        method: "delete",
        body: { uids: uidsStudentsInscriptions },
        toast: true,
        loader: true,
        stringify: true,
    };

    apiFetch(params)
        .then(() => {
            reloadStudentsTable();
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

function getUidsStudentsInscriptions() {
    return selectedEducationalProgramStudents.map((student) => {
        return student.educational_program_student_info.uid;
    });
}

function enrollStudentsCsv() {
    const fileInput = document.getElementById("attachment");
    const file = fileInput.files[0];

    const formData = new FormData();
    formData.append("attachment", file);
    formData.append("educational_program_uid", selectedEducationalProgramUid);

    const params = {
        url: "/learning_objects/educational_program/enroll_students_csv",
        method: "POST",
        body: formData,
        toast: true,
        loader: true,
    };

    apiFetch(params)
        .then(() => {
            hideModal("enroll-educational-program-csv-modal");
            reloadStudentsTable();
        })
        .catch((data) => {
            showFormErrors(data.errors);
        });
}

function handleNewEditionEducationalProgram(educationalProgramUid) {
    showModalConfirmation(
        "Nueva edición de programa",
        "¿Estás seguro de que quieres crear una nueva edición del programa formativo seleccionado?"
    ).then((result) => {
        if (!result) return;
        editionOrDuplicationEducationalProgram(
            educationalProgramUid,
            "edition"
        );
    });
}

function handleDuplicationEducationalProgram(educationalProgramUid) {
    showModalConfirmation(
        "¿Deseas duplicar esta edición?",
        "Se creará una nueva edición del programa formativo con los mismos datos que la edición actual.",
        "duplicateEducationalProgram"
    ).then((result) => {
        if (result)
            editionOrDuplicationEducationalProgram(
                educationalProgramUid,
                "duplication"
            );
    });
}

function editionOrDuplicationEducationalProgram(educationalProgramUid, action) {
    const params = {
        url: "/learning_objects/educational_program/edition_or_duplicate_educational_program",
        method: "POST",
        body: {
            educationalProgramUid,
            action,
        },
        toast: true,
        stringify: true,
        loader: true
    };

    apiFetch(params).then((response) => {
        reloadTableEducationalPrograms();
        loadEducationalProgramModal(response.educational_program_uid);
    });
}

function reloadTableEducationalPrograms() {
    educationalProgramsTable.setData(endPointTable);
}

function downloadDocument(uidDocument) {
    const params = {
        url: "/learning_objects/educational_program/download_document_student",
        method: "POST",
        body: { uidDocument: uidDocument },
        toast: false,
        loader: true,
        stringify: true,
        download: true,
    };

    apiFetch(params);
}

function generateTags() {
    const text = document.getElementById("description").value;

    if (!text) {
        showToast("No hay descripción para generar etiquetas", "error");
        return;
    }

    const params = {
        url: "/learning_objects/generate_tags",
        method: "POST",
        body: { text },
        loader: true,
        stringify: true,
    };

    apiFetch(params)
        .then((data) => {
            data.forEach((tag) => {
                tomSelectTags.addOption({ value: tag, text: tag });
                tomSelectTags.addItem(tag);
            });
        })
        .catch(() => {
            showToast("No se han podido generar las etiquetas", "error");
        });
}

function updatePaymentMode(paymentMode) {
    if (paymentMode == "SINGLE_PAYMENT") {
        document.getElementById("cost").classList.remove("hidden");
        document.getElementById("payment_terms").classList.add("hidden");
        document
            .getElementById("label-container-cost")
            .classList.add("label-center");
    } else {
        document.getElementById("cost").classList.add("hidden");
        document
            .getElementById("label-container-cost")
            .classList.remove("label-center");

        // Si no hay términos de pago, añadimos uno por defecto
        if (!document.querySelector(".payment-term")) {
            addPaymentTerm();
        }

        document.getElementById("payment_terms").classList.remove("hidden");
    }
}

function addPaymentTerm() {
    const template = document
        .getElementById("payment-term-template")
        .content.cloneNode(true);
    document.getElementById("payment-terms-list").appendChild(template);
}

function removePaymentTerm(event) {
    let target = event.target;

    if (!target.classList.contains(".btn-remove-payment-term")) {
        target = target.closest(".btn-remove-payment-term");
    }

    if (target) {
        target.closest(".payment-term").remove();
    }
}

function getPaymentTerms() {
    const coursePaymentTerms = document
        .getElementById("payment-terms-list")
        .querySelectorAll(".payment-term");

    const paymentTermsData = [];

    coursePaymentTerms.forEach((coursePaymentTerm) => {
        let paymentTermData = {
            uid: coursePaymentTerm.dataset.paymentTermUid ?? null,
            name: coursePaymentTerm.querySelector(".payment-term-name").value,
            start_date: coursePaymentTerm.querySelector(
                ".payment-term-start-date"
            ).value,
            finish_date: coursePaymentTerm.querySelector(
                ".payment-term-finish-date"
            ).value,
            cost: coursePaymentTerm.querySelector(".payment-term-cost").value,
        };

        paymentTermsData.push(paymentTermData);
    });

    return paymentTermsData;
}

function loadPaymentTerms(paymentTerms) {
    // Limpiar el contenedor de términos de pago
    const containerPaymentTerms = document.getElementById("payment-terms-list");
    containerPaymentTerms.innerHTML = "";

    // Añadir cada término de pago al contenedor
    paymentTerms.forEach((paymentTerm) => {
        const paymentTermTemplate = document
            .getElementById("payment-term-template")
            .content.cloneNode(true);

        paymentTermTemplate.querySelector(
            ".payment-term"
        ).dataset.paymentTermUid = paymentTerm.uid;
        paymentTermTemplate.querySelector(
            ".payment-term"
        ).dataset.paymentTermUid = paymentTerm.uid;
        paymentTermTemplate.querySelector(".payment-term-name").value =
            paymentTerm.name;
        paymentTermTemplate.querySelector(".payment-term-start-date").value =
            paymentTerm.start_date;
        paymentTermTemplate.querySelector(".payment-term-finish-date").value =
            paymentTerm.finish_date;
        paymentTermTemplate.querySelector(".payment-term-cost").value =
            paymentTerm.cost;
        containerPaymentTerms.appendChild(paymentTermTemplate);
    });
}

function showArea(area, show) {
    if (show) area.classList.remove("hidden");
    else area.classList.add("hidden");
}
