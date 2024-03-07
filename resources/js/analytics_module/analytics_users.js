import { TabulatorFull as Tabulator } from "tabulator-tables";
import { tabulatorBaseConfig } from "../tabulator_handler";

const endPointTable = "/analytics/users/get_user_roles";

let analyticsUsersTable;



document.addEventListener("DOMContentLoaded", function () {


    const columns = [
        { title: "Rol", field: "name", widthGrow: 8 },
        {
            title: "Nº usuarios registrados",
            field: "users_count",
            widthGrow: 2,
        },
    ];

    analyticsUsersTable = new Tabulator("#analytics-users-table", {
        ajaxURL: endPointTable,
        ajaxConfig: "GET",
        ...tabulatorBaseConfig,
        ajaxResponse: async function (url, params, response) {

            return {
                last_page: response.last_page,
                data: response.data,
            };
        },
        columns: columns,
    });


});
