import { TabulatorFull as Tabulator } from "tabulator-tables";
import {
    tabulatorBaseConfig,
    updatePaginationInfo,
    controlsPagination,
    controlsSearch,
} from "../tabulator_handler";
import { heroicon } from "../heroicons.js";
import { showModal } from "../modal_handler";

let studentsTable;
let coursesStudentTable;
let endPointStudentsTable = "/credentials/students/get_students";
let endPointCoursesStudentTable = "/credentials/students/get_courses_student";

document.addEventListener("DOMContentLoaded", () => {
    initializeStudentsCredentialsTable();
    controlsPagination(studentsTable, "students-table");
    controlsSearch(studentsTable, endPointStudentsTable, "students-table");
});

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
                    <button type="button" class='btn action-btn'>${heroicon(
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
}

function loadListCoursesStudentTable(studentUid) {
    const columns = [
        { title: "Título", field: "title", widthGrow: 5 },
        {
            title: "Credencial",
            field: "pivot.credential",
            formatter: function (cell, formatterParams, onRendered) {
                const credential = cell.getRow().getData().pivot.credential;

                if (!credential) {
                    return `<a href="javascript:void(0)" class="link-icon">Generar credencial ${heroicon(
                        "arrow-path",
                        "outline"
                    )}</a>`;
                } else {
                    return `<a href="javascript:void(0)" class="link-icon">Descargar credencial ${heroicon(
                        "arrow-down-tray",
                        "outline"
                    )}</a>`;
                }
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

    showModal("courses-student-modal");
}
