window.addEventListener("load", function () {
    let botones = document.querySelectorAll(".boton-solicitar");

    for (let boton of botones) {
        boton.onclick = async function () {
            let modal = new Modal("Solicitar servicio");
            await modal.cargarURL("/plantillas/formSolicitud.html");
            modal.abrir();

            let form = document.forms["solicitarServicio"];
            let errorBox = document.getElementById("solicitudError");
            let total = document.getElementById("solicitudTotal");
            let puntosHora = Number(boton.dataset.puntos);

            document.getElementById("solicitudProfesional").textContent = boton.dataset.profesional;

            // No dejar elegir una fecha pasada: mínimo, dentro de una hora
            let minimo = new Date(Date.now() + 60 * 60 * 1000);
            minimo.setMinutes(minimo.getMinutes() - minimo.getTimezoneOffset());
            form["fecha"].min = minimo.toISOString().slice(0, 16);

            // Cálculo en vivo: horas × puntos/hora
            function calcular() {
                let horas = Number(form["horas"].value) || 0;
                let precio = Math.ceil(horas * puntosHora);
                total.textContent = horas + " h × " + puntosHora + " pts/h = " + precio + " pts";
            }
            form["horas"].oninput = calcular;
            calcular();

            form.onsubmit = async function (event) {
                event.preventDefault();
                errorBox.hidden = true;

                let respuesta = await fetch("/api/solicitudes", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        idOferta: Number(boton.dataset.id),
                        fecha: form["fecha"].value,
                        horas: Number(form["horas"].value)
                    })
                });
                let resultado = await respuesta.json();

                if (respuesta.status === 422 && resultado.errores) {
                    errorBox.textContent = Object.values(resultado.errores).join(" ");
                    errorBox.hidden = false;
                    return;
                }
                if (!respuesta.ok) {
                    errorBox.textContent = resultado.error || "No se pudo enviar la solicitud.";
                    errorBox.hidden = false;
                    return;
                }

                // Enviada: mostrar confirmación dentro del mismo modal
                modal.cargarHTML(
                    '<p>Solicitud enviada. El profesional tiene que aceptarla. ' +
                    'Se han reservado <strong>' + resultado.precio + ' pts</strong> de tu saldo: pasarán al profesional cuando los dos confirméis el servicio, y si se rechaza o se cancela te los devolvemos.</p>' +
                    '<div class="botones"><button class="boton" type="button" ' +
                    'onclick="document.body.modal.cerrarModal()">Entendido</button></div>'
                );
            };
        };
    }
});