import { TabulatorFull as Tabulator } from "tabulator-tables";
import {
    tabulatorBaseConfig,
    updatePaginationInfo,
    controlsPagination,
    controlsSearch,
} from "../tabulator_handler";
import { heroicon } from "../heroicons.js";
import { showModal } from "../modal_handler";
import { showToast } from "../toast.js";
import { apiFetch } from "../app.js";

let studentsTable;
let coursesStudentTable;
let endPointStudentsTable = "/credentials/students/get_students";
let endPointCoursesStudentTable = "/credentials/students/get_courses_student";

let selectedCourses = [];
let selectedEducationalPrograms = [];
let selectedStudent = null;

document.addEventListener("DOMContentLoaded", () => {
    initHandlers();
    initializeStudentsCredentialsTable();
});

function initHandlers() {
    const buttons = [
        { id: "emit-credentials-btn", action: "emit" },
        { id: "send-credentials-btn", action: "send" },
        { id: "seal-credentials-btn", action: "seal" },
    ];

    buttons.forEach((button) => {
        document.getElementById(button.id).addEventListener("click", () => {
            if (!selectedCourses.length && !selectedEducationalPrograms.length) {
                showToast("No has seleccionado ningún objeto de aprendizaje", "error");
                return;
            }

            actionCredentials(button.action);
        });
    });
}

async function actionCredentials(action) {
    let url = "";

    if (action == "emit") url = "/credentials/students/emit_credentials";
    else if (action == "send") url = "/credentials/students/send_credentials";
    else if (action == "seal") url = "/credentials/students/seal_credentials";
    else return;

    const params = {
        url,
        method: "POST",
        loader: true,
        body: {
            courses: selectedCourses,
            educational_programs: selectedEducationalPrograms,
            user_uid: document.getElementById("user-uid").value,
        },
        stringify: true,
        toast: true,
    };

    await apiFetch(params);

    reloadCoursesStudentsTable();
}

function initializeStudentsCredentialsTable() {
    const columns = [
        { title: "Nombre", field: "first_name", widthGrow: 5 },
        { title: "Apellidos", field: "last_name", widthGrow: 5 },
        { title: "Email", field: "email", widthGrow: 5 },
        {
            title: "",
            field: "actions",
            formatter: function (cell, formatterParams, onRendered) {
                return `
                    <button type="button" class='btn action-btn' title='Ver'>${heroicon(
                        "eye",
                        "outline"
                    )}</button>
                `;
            },
            cellClick: function (e, cell) {
                e.preventDefault();

                const student = cell.getRow().getData();
                const coursesStudentTitle = document.getElementById(
                    "courses-student-modal-title"
                );
                coursesStudentTitle.innerHTML = `Cursos de ${student.first_name} ${student.last_name}`;
                loadListCoursesStudentTable(student.uid);
            },
            cssClass: "text-center",
            headerSort: false,
            width: 30,
            resizable: false,
        },
    ];

    studentsTable = new Tabulator("#students-table", {
        ajaxURL: endPointStudentsTable,
        ...tabulatorBaseConfig,
        ajaxResponse: function (url, params, response) {
            updatePaginationInfo(studentsTable, response, "students-table");

            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });

    controlsPagination(studentsTable, "students-table");
    controlsSearch(studentsTable, endPointStudentsTable, "students-table");
}

function loadListCoursesStudentTable(studentUid) {
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
                        updateSelectedLearningObjects(checkbox, row.getData());
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

                updateSelectedLearningObjects(checkbox, rowData);
            },
            headerSort: false,
            width: 60,
        },
        { title: "Título", field: "title", widthGrow: 5 },
        {
            title: "Tipo",
            field: "learning_object_type",
            widthGrow: 3,
            formatter: function (cell, formatterParams, onRendered) {
                const type = cell.getRow().getData().learning_object_type;
                if (type === "course") return "Curso";
                else if (type === "educational_program")
                    return "Programa educativo";
            },
        },
        {
            title: "Estado de la credencial",
            field: "emissions_block_uuid",
            formatter: function (cell, formatterParams, onRendered) {
                const credential = cell.getRow().getData().emissions_block_uuid;
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
                const credentialSent = cell.getRow().getData().credential_sent;
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
                const credentialSealed = cell
                    .getRow()
                    .getData().credential_sealed;
                return credentialSealed ? "Sí" : "No";
            },
            cellClick: function (e, cell) {},
            widthGrow: 3,
            resizable: false,
        },
    ];

    if (coursesStudentTable) coursesStudentTable.destroy();

    coursesStudentTable = new Tabulator("#courses-student-table", {
        ajaxURL: endPointCoursesStudentTable + "/" + studentUid,
        ...tabulatorBaseConfig,
        ajaxResponse: function (url, params, response) {
            updatePaginationInfo(
                coursesStudentTable,
                response,
                "courses-student-table"
            );

            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });

    controlsSearch(
        coursesStudentTable,
        endPointCoursesStudentTable + "/" + studentUid,
        "courses-student-table"
    );

    controlsPagination(coursesStudentTable, "courses-student-table");

    document.getElementById("user-uid").value = studentUid;
    selectedStudent = studentUid;

    showModal("courses-student-modal");
}

function reloadCoursesStudentsTable() {
    coursesStudentTable.setData(
        endPointCoursesStudentTable + "/" + selectedStudent
    );
}

function updateSelectedLearningObjects(checkbox, rowData) {
    if (checkbox.checked) {
        if (rowData.learning_object_type == "course") {
            selectedCourses.push(rowData.uid);
        } else {
            selectedEducationalPrograms.push(rowData.uid);
        }
    } else {
        if (rowData.learning_object_type == "course") {
            const index = selectedCourses.findIndex(
                (course) => course === rowData.uid
            );
            if (index > -1) {
                selectedCourses.splice(index, 1);
            }
        } else {
            const index = selectedEducationalPrograms.findIndex(
                (program) => program === rowData.uid
            );
            if (index > -1) {
                selectedEducationalPrograms.splice(index, 1);
            }
        }
    }
}
