window.addEventListener("load", function () {
    // ---------- Pestañas ----------
    let pestanas = document.querySelectorAll(".sol-pestana");

    function mostrar(pestana) {
        for (let p of pestanas) {
            let activa = p === pestana;
            p.setAttribute("aria-selected", activa ? "true" : "false");
            document.getElementById(p.getAttribute("aria-controls")).hidden = !activa;
        }
        // Recordar la pestaña en la URL (#enviadas) para que sobreviva a una recarga
        history.replaceState(null, "", "#" + pestana.id.replace("tab-", ""));
    }

    for (let pestana of pestanas) {
        pestana.onclick = function () {
            mostrar(pestana);
        };
    }

    // Al cargar, abrir la pestaña que diga la URL
    let inicial = document.getElementById("tab-" + location.hash.slice(1));
    if (inicial) {
        mostrar(inicial);
    }

    // ---------- Acciones: aceptar, rechazar, cancelar ----------
    let mensajes = {
        rechazar: "¿Seguro que quieres rechazar esta solicitud?",
        cancelar: "¿Seguro que quieres cancelar esta solicitud?"
    };

    for (let boton of document.querySelectorAll(".sol-boton[data-accion]")) {
        boton.onclick = async function () {
            let accion = boton.dataset.accion;

            // Rechazar y cancelar no tienen vuelta atrás: pedir confirmación
            if (mensajes[accion] && !confirm(mensajes[accion])) {
                return;
            }

            let tarjeta = boton.closest(".sol-tarjeta");
            let errorBox = tarjeta.querySelector(".sol-error");
            errorBox.hidden = true;
            boton.disabled = true;

            let respuesta = await fetch("/api/solicitudes/" + boton.dataset.id + "/" + accion, {
                method: "PUT"
            });
            let resultado = await respuesta.json();

            if (!respuesta.ok) {
                errorBox.textContent = resultado.error || "No se pudo completar la acción.";
                errorBox.hidden = false;
                boton.disabled = false;
                return;
            }

            // Hecho: recargar para ver el nuevo estado (la pestaña se mantiene por el #)
            location.reload();
        };
    }
});