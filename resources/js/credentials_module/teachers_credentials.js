import { TabulatorFull as Tabulator } from "tabulator-tables";
import {
    tabulatorBaseConfig,
    updatePaginationInfo,
    controlsPagination,
    controlsSearch,
} from "../tabulator_handler.js";
import { heroicon } from "../heroicons.js";
import {
    hideModal,
    showModal,
    showModalConfirmation,
} from "../modal_handler.js";
import { apiFetch } from "../app.js";
import { showToast } from "../toast.js";

let teachersTable;
let coursesTeacherTable;
let endPointTeachersTable = "/credentials/teachers/get_teachers";
let endPointCoursesTeacherTable = "/credentials/teachers/get_courses_teacher";
let selectedCourses = [];

document.addEventListener("DOMContentLoaded", () => {
    initializeTeachersCredentialsTable();
    controlsPagination(teachersTable, "teachers-table");
    controlsSearch(teachersTable, endPointTeachersTable, "teachers-table");
    initHandlers();
});

function initializeTeachersCredentialsTable() {
    const columns = [
        { title: "Nombre", field: "first_name", widthGrow: 5 },
        { title: "Apellidos", field: "last_name", widthGrow: 5 },
        { title: "Email", field: "email", widthGrow: 5 },
        {
            title: "",
            field: "actions",
            formatter: function (cell, formatterParams, onRendered) {
                return `
                    <button type="button" class='btn action-btn'>${heroicon(
                        "eye",
                        "outline"
                    )}</button>
                `;
            },
            cellClick: function (e, cell) {
                e.preventDefault();

                const teacher = cell.getRow().getData();
                const coursesTeacherTitle = document.getElementById(
                    "courses-teacher-modal-title"
                );
                coursesTeacherTitle.innerHTML = `Cursos de ${teacher.first_name} ${teacher.last_name}`;
                loadListCoursesTeacherTable(teacher.uid);
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
    ];

    teachersTable = new Tabulator("#teachers-table", {
        ajaxURL: endPointTeachersTable,
        ...tabulatorBaseConfig,
        ajaxResponse: function (url, params, response) {
            updatePaginationInfo(teachersTable, response, "teachers-table");

            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });
}

function loadListCoursesTeacherTable(teacherUid) {
    const columns = [
        {
            title: '<div class="checkbox-cell"><input type="checkbox" id="select-all-checkbox"/></div>',
            headerClick: function (e) {
                const selectAllCheckbox = e.target;
                if (selectAllCheckbox.type === "checkbox") {
                    // Asegúrate de que el clic fue en el checkbox
                    coursesStudentTable.getRows().forEach((row) => {
                        const cell = row.getCell("select");
                        const checkbox = cell
                            .getElement()
                            .querySelector('input[type="checkbox"]');
                        checkbox.checked = selectAllCheckbox.checked;
                        updateSelectedCourses(checkbox, row.getData());
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
                const checkbox = e.target;
                const rowData = cell.getRow().getData();

                updateSelectedCourses(checkbox, rowData);
            },
            headerSort: false,
            width: 60,
        },
        { title: "Título", field: "title", widthGrow: 5 },
        {
            title: "Generación de credencial",
            field: "courses.certidigital_teacher_credential_uid",
            formatter: function (cell, formatterParams, onRendered) {
                const credential = cell
                    .getRow()
                    .getData().certidigital_teacher_credential_uid;
                return credential ? "Generada" : "No generada";
            },
            cellClick: function (e, cell) {},
            widthGrow: 3,
            resizable: false,
        },
        {
            title: "Estado de la credencial",
            field: "courses_teachers.emissions_block_uuid",
            formatter: function (cell, formatterParams, onRendered) {
                const credential = cell.getRow().getData()
                    .courses_teachers.emissions_block_uuid;
                return credential ? "Emitida" : "No emitida";
            },
            cellClick: function (e, cell) {},
            widthGrow: 3,
            resizable: false,
        },
        {
            title: "Credencial enviada",
            field: "courses_teachers.credential_sent",
            formatter: function (cell, formatterParams, onRendered) {
                const credentialSent = cell.getRow().getData()
                    .courses_teachers.credential_sent;
                return credentialSent ? "Sí" : "No";
            },
            cellClick: function (e, cell) {},
            widthGrow: 3,
            resizable: false,
        },
        {
            title: "Credencial sellada",
            field: "courses_teachers.credential_sealed",
            formatter: function (cell, formatterParams, onRendered) {
                const credentialSealed = cell.getRow().getData()
                    .courses_teachers.credential_sealed;
                return credentialSealed ? "Sí" : "No";
            },
            cellClick: function (e, cell) {},
            widthGrow: 3,
            resizable: false,
        },
    ];

    if (coursesTeacherTable) coursesTeacherTable.destroy();

    coursesTeacherTable = new Tabulator("#courses-teacher-table", {
        ajaxURL: endPointCoursesTeacherTable + "/" + teacherUid,
        ...tabulatorBaseConfig,
        ajaxResponse: function (url, params, response) {
            updatePaginationInfo(
                coursesTeacherTable,
                response,
                "courses-teacher-table"
            );

            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });

    controlsSearch(
        coursesTeacherTable,
        endPointCoursesTeacherTable + "/" + teacherUid,
        "courses-teacher-table"
    );

    controlsPagination(coursesTeacherTable, "courses-teacher-table");

    document.getElementById("user-uid").value = teacherUid;
    handleModalCoursesTeachers(true);
}

function initHandlers() {
    const buttons = [
        { id: "generate-credentials-btn", action: "generate" },
        { id: "emit-credentials-btn", action: "emit" },
        { id: "send-credentials-btn", action: "send" },
        { id: "seal-credentials-btn", action: "seal" },
    ];

    buttons.forEach((button) => {
        document.getElementById(button.id).addEventListener("click", () => {
            if (!selectedCourses.length) {
                showToast(
                    "No has seleccionado ningún objeto de aprendizaje",
                    "error"
                );
                return;
            }

            actionCredentials(button.action);
        });
    });
}

async function actionCredentials(action) {
    let url = "";
    let title = "";
    let message = "";

    if (action == "generate") {
        url = "/credentials/teachers/generate_credentials";
        title = "Generar credenciales";
        message =
            "¿Está seguro que desea generar las credenciales seleccionadas?";
    } else if (action == "emit") {
        url = "/credentials/teachers/emit_credentials";
        title = "Emitir credenciales";
        message =
            "¿Está seguro que desea emitir las credenciales seleccionadas?";
    } else if (action == "send") {
        url = "/credentials/teachers/send_credentials";
        title = "Enviar credenciales";
        message =
            "¿Está seguro que desea enviar las credenciales seleccionadas?";
    } else if (action == "seal") {
        url = "/credentials/teachers/seal_credentials";
        title = "Sellar credenciales";
        message =
            "¿Está seguro que desea sellar las credenciales seleccionadas?";
    }

    showModalConfirmation(title, message).then((result) => {
        if (!result) {
            return;
        }

        const params = {
            url,
            method: "POST",
            loader: true,
            body: {
                courses: selectedCourses,
                user_uid: document.getElementById("user-uid").value,
            },
            stringify: true,
            toast: true,
        };

        apiFetch(params).then(() => {
            reloadCoursesTeacherTable();
        });
    });
}

function handleModalCoursesTeachers(show) {
    if (show) showModal("courses-teacher-modal");
    else hideModal("courses-teacher-modal");
}

function updateSelectedCourses(checkbox, rowData) {
    if (checkbox.checked) {
        selectedCourses.push(rowData.uid);
    } else {
        const index = selectedCourses.findIndex(
            (course) => course === rowData.uid
        );
        if (index > -1) {
            selectedCourses.splice(index, 1);
        }
    }
}

function reloadCoursesTeacherTable() {
    coursesTeacherTable.setData();
}
