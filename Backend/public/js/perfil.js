window.addEventListener("load", function () {
    let botones = document.querySelectorAll(".perfil-editar");

    for (let boton of botones) {
        boton.onclick = async function () {
            let modal = new Modal("Editar oferta");
            await modal.cargarURL("/plantillas/formOferta.html");
            modal.abrir();

            // Rellenar el formulario con los datos de la oferta pulsada
            let form = document.forms["editarOferta"];
            let errorBox = document.getElementById("ofertaError");

            document.getElementById("ofertaServicio").textContent = boton.dataset.servicio;
            form["puntosHora"].value = boton.dataset.puntos;
            form["descripcion"].value = boton.dataset.descripcion;
            form["activo"].checked = boton.dataset.activo === "1";

            form.onsubmit = async function (event) {
                event.preventDefault();
                errorBox.hidden = true;

                let respuesta = await fetch("/api/ofertas/" + boton.dataset.id, {
                    method: "PUT",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        puntosHora: Number(form["puntosHora"].value),
                        descripcion: form["descripcion"].value.trim(),
                        activo: form["activo"].checked
                    })
                });
                let resultado = await respuesta.json();

                if (respuesta.status === 422) {
                    errorBox.textContent = Object.values(resultado.errores).join(" ");
                    errorBox.hidden = false;
                    return;
                }
                if (!respuesta.ok) {
                    errorBox.textContent = resultado.error || "No se pudo guardar la oferta.";
                    errorBox.hidden = false;
                    return;
                }

                // Guardado: recargar para ver los cambios en la lista
                location.reload();
            };
        };
    }
});