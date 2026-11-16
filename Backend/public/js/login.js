window.addEventListener("load", function () {
    let form = document.getElementById("formLogin");
    if (!form) {
        return;
    }

    let errorBox = document.getElementById("loginError");
    let boton = form.querySelector("button[type=submit]");

    form.onsubmit = async function (event) {
        event.preventDefault();
        errorBox.hidden = true;
        boton.disabled = true; // evita enviar dos códigos con un doble clic

        try {
            let respuesta = await fetch("/api/auth/login", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    email: form["correo"].value.trim(),
                    contrasena: form["contrasena"].value
                })
            });
            let resultado = await respuesta.json();

            if (!respuesta.ok) {
                errorBox.textContent = resultado.error || "No se pudo iniciar sesión.";
                errorBox.hidden = false;
                return;
            }

            // Contraseña correcta y código enviado: pedirlo en el modal
            let modal = new Modal("Verificación en dos pasos");
            await modal.cargarURL("/plantillas/formCodigo.html");
            await modal.cargarLogica("/js/codigo.js");
            modal.abrir();
        } finally {
            boton.disabled = false;
        }
    };
});