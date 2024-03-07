import { TabulatorFull as Tabulator } from "tabulator-tables";
import {
    tabulatorBaseConfig,
    updatePaginationInfo,
    controlsPagination,
    controlsSearch,
} from "../tabulator_handler.js";
import { heroicon } from "../heroicons.js";
import { hideModal, showModal } from "../modal_handler.js";

let teachersTable;
let coursesTeacherTable;
let endPointTeachersTable = "/credentials/teachers/get_teachers";
let endPointCoursesTeacherTable = "/credentials/teachers/get_courses_teacher";

document.addEventListener("DOMContentLoaded", () => {
    initializeTeachersCredentialsTable();
    controlsPagination(teachersTable, "teachers-table");
    controlsSearch(teachersTable, endPointTeachersTable, "teachers-table");
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

    handleModalCoursesTeachers(true);
}

function handleModalCoursesTeachers(show) {
    const coursesTeacherModal = document.getElementById(
        "courses-teacher-modal"
    );
    if (show) showModal(coursesTeacherModal);
    else hideModal(coursesTeacherModal);
}
