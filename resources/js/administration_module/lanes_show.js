import { apiFetch } from "../app.js";

document.addEventListener("DOMContentLoaded", function () {
    initHandlers();
});

function initHandlers() {
    document
        .getElementById("lanes-show-form")
        .addEventListener("submit", submitLanesShowForm);
}

function submitLanesShowForm() {
    const formData = new FormData(this);

    formData.append("lane_recents_courses", document.getElementById("lane_recents_courses").checked ? "1" : "0");
    formData.append("lane_recents_educational_programs", document.getElementById("lane_recents_educational_programs").checked ? "1" : "0");
    formData.append("lane_recents_educational_resources", document.getElementById("lane_recents_educational_resources").checked ? "1" : "0");
    formData.append("lane_recents_itineraries", document.getElementById("lane_recents_itineraries").checked ? "1" : "0");


    const params = {
        url: "/administration/lanes_show/save_lanes_show",
        method: "POST",
        body: formData,
        toast: true,
        loader: true,
    };

    apiFetch(params);
}
