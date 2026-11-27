(function () {
    let form = document.forms["registrarUsuario"];
    let categoria = form["categoria"];
    let servicios = form["servicios"];
    let serviciosSelec = form["serviciosSelec"];
    let contrasena = form["contrasena"];
    let confirmar = form["confirmar"];
    let aviso = document.getElementById("avisoConfirmar");
    let errorBox = document.getElementById("registroError");

    async function traerCategorias() {
        let respuesta = await fetch("/api/categorias");
        let lista = await respuesta.json();

            for (let c of lista) {
                let opcion = document.createElement("option");
                opcion.value = c.id;
                opcion.textContent = c.nombre;
                categoria.appendChild(opcion);
            }
    }

    async function traerServicios(idCategoria) {
        let respuesta = await fetch("/api/servicios?categoria=" + idCategoria);
        let lista = await respuesta.json();

        servicios.innerHTML = "";

        for (let s of lista) {
            // Si ya está elegido, no se vuelve a mostrar
            let yaElegido = false;
            for (let elegido of serviciosSelec.options) {
                if (elegido.value === String(s.id)) {
                    yaElegido = true;
                    break;
                }
            }
            if (yaElegido) {
                continue;
            }

            let opcion = document.createElement("option");
            opcion.value = s.id;
            opcion.textContent = s.nombre;
            opcion.title = s.descripcion;

            servicios.appendChild(opcion);
        }
    }

    categoria.onchange = function () {
        traerServicios(categoria.value);
    };

    // Elegir: lo marcado en "Servicios" pasa a "Seleccionados"
    servicios.onchange = function () {
        for (let opcion of Array.from(servicios.selectedOptions)) {
            opcion.selected = false; // desmarcarla antes de moverla
            serviciosSelec.appendChild(opcion);
        }
    };

    // Quitar: lo marcado en "Seleccionados" se elimina y se recarga la lista
    serviciosSelec.onchange = function () {
        for (let opcion of Array.from(serviciosSelec.selectedOptions)) {
            opcion.remove();
        }
        if (categoria.value) {
            traerServicios(categoria.value);
        }
    };

    function comprobarContrasenas() {
        if (confirmar.value === "") {
            aviso.hidden = true;
            confirmar.classList.remove("invalido");
            return;
        }

        aviso.hidden = false;
        if (contrasena.value === confirmar.value) {
            aviso.textContent = "Las contraseñas coinciden";
            aviso.className = "nota verde";
            confirmar.classList.remove("invalido");
        } else {
            aviso.textContent = "Las contraseñas no coinciden";
            aviso.className = "nota rojo";
            confirmar.classList.add("invalido");
        }
    }

    contrasena.oninput = comprobarContrasenas;
    confirmar.oninput = comprobarContrasenas;

    // ---------- Currículum ----------

    // Devuelve el texto del error, o "" si el fichero vale
    function comprobarCv(fichero) {
        if (!fichero) {
            return "Tienes que adjuntar tu currículum en PDF.";
        }
        // En algunos Windows el navegador no rellena type, así que miramos también la extensión.
        // La comprobación de verdad la hace el servidor mirando el contenido.
        if (fichero.type !== "application/pdf" && !fichero.name.toLowerCase().endsWith(".pdf")) {
            return "El currículum tiene que ser un PDF.";
        }
        if (fichero.size > 2 * 1024 * 1024) {
            return "El currículum no puede pasar de 2 MB.";
        }
        return "";
    }

    function mostrarError(mensaje) {
        errorBox.textContent = mensaje;
        errorBox.hidden = false;
    }

    async function registrarUsuario() {
        if (contrasena.value !== confirmar.value) {
            mostrarError("Las contraseñas no coinciden.");
            return;
        }
        errorBox.hidden = true;

        let fichero = form["cv"].files[0];
        let errorCv = comprobarCv(fichero);
        if (errorCv) {
            mostrarError(errorCv);
            return;
        }

        // Todo va en un único objeto FormData: los textos y el PDF tal cual, sin convertir
        let datos = new FormData();
        datos.append("nombre", form["nombre"].value.trim());
        datos.append("apellidos", form["apellidos"].value.trim());
        datos.append("email", form["email"].value.trim());
        datos.append("nacimiento", form["nacimiento"].value);
        datos.append("contrasena", contrasena.value);

        // Las opciones de serviciosSelec no están "seleccionadas", así que las añadimos a mano.
        // Con los corchetes, PHP las junta en un array: $_POST["servicios"]
        for (let opcion of serviciosSelec.options) {
            datos.append("servicios[]", opcion.value);
        }

        datos.append("cv", fichero);

        // Sin cabecera Content-Type: la pone el navegador (multipart/form-data con su separador)
        let respuesta = await fetch("/api/auth/registro", {
            method: "POST",
            body: datos
        });
        let resultado = await respuesta.json();

        if (respuesta.status === 422 || respuesta.status === 409) {
            mostrarError(Object.values(resultado.errores).join(" "));
            return;
        }

        if (!respuesta.ok) {
            mostrarError(resultado.error || "No se pudo completar el registro.");
            return;
        }

        document.body.modal.cerrarModal();
        location.reload();

    }

    form["guardar"].onclick = function (event) {
        event.preventDefault();
        registrarUsuario();
    };

    traerCategorias();
})();