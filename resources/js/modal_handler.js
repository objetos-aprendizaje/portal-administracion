document.addEventListener("DOMContentLoaded", function () {

    // Evento para cerrar el modal
    let closeButtons = document.querySelectorAll(".close-modal-btn");
    closeButtons.forEach(function (button) {
        button.addEventListener("click", function (e) {
            e.stopImmediatePropagation();
            const modalId = this.getAttribute("data-modal-id");
            hideModal(modalId);
        });
    });
});

// Mostrar el modal
export function showModal(modalId, title = false) {

    const modal = document.getElementById(modalId);
    modal.style.display = 'block';

    if (title) modal.querySelector('.modal-title').textContent = title;
    setTimeout(() => {
        modal.style.opacity = '1';
        modal.classList.add('open');
        document.body.classList.add('modal-open');
    }, 10);
}

// Ocultar el modal
export function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.style.opacity = '0';
    setTimeout(() => {
        modal.style.display = 'none';
        modal.classList.remove('open');
        document.body.classList.remove('modal-open');
    }, 300);
}



export function showModalConfirmation(title, description, action, params) {
    return new Promise((resolve) => {
        const confirmationModal = document.getElementById("confirmation-modal");

        confirmationModal.querySelector("#modal-title").textContent = title;
        confirmationModal.querySelector("#modal-description").textContent =
            description;
        confirmationModal.setAttribute("data-action", action);

        const paramsContainer = confirmationModal.querySelector(".params");
        paramsContainer.innerHTML = "";

        if (params && params.length) {
            params.forEach((param) => {
                const input = document.createElement("input");
                input.type = "hidden";
                input.className = param.key;
                input.value = param.value;
                paramsContainer.appendChild(input);
            });
        }

        // Asociar los eventos para los botones de Confirmar y Cancelar
        confirmationModal
            .querySelector("#confirm-button")
            .addEventListener("click", function () {
                resolve(true);
                hideModal("confirmation-modal");
            });

        confirmationModal
            .querySelector("#cancel-button")
            .addEventListener("click", function () {
                resolve(false);
                hideModal("confirmation-modal");
            });

        showModal("confirmation-modal");
    });
}
