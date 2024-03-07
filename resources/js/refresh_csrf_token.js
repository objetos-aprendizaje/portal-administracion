document.addEventListener("DOMContentLoaded", function () {
    const timeUpdateCrsfToken = window.sessionLifetime * 1000 - 10000;

    setInterval(() => {
        console.log('refresco');
        fetch("/refresh-csrf")
            .then((response) => response.text())
            .then((token) => {
                // Actualizar todos los tokens en formularios
                document
                    .querySelectorAll('input[name="_token"]')
                    .forEach((el) => (el.value = token));

                // Actualizar el token en el meta tag si existe
                const metaCsrf = document.querySelector(
                    'meta[name="csrf-token"]'
                );
                if (metaCsrf) {
                    metaCsrf.setAttribute("content", token);
                }
            })
            .catch((error) =>
                console.error("Error al actualizar el token CSRF:", error)
            );
    }, timeUpdateCrsfToken);
});
