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
            confirmar.classList.remove("reg-invalido");
            return;
        }

        aviso.hidden = false;
        if (contrasena.value === confirmar.value) {
            aviso.textContent = "Las contraseñas coinciden";
            aviso.className = "reg-aviso reg-aviso--ok";
            confirmar.classList.remove("reg-invalido");
        } else {
            aviso.textContent = "Las contraseñas no coinciden";
            aviso.className = "reg-aviso reg-aviso--error";
            confirmar.classList.add("reg-invalido");
        }
    }

    contrasena.oninput = comprobarContrasenas;
    confirmar.oninput = comprobarContrasenas;

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

        // FormData se salta las opciones no "seleccionadas",
        // así que recorremos serviciosSelec a mano
        let ids = [];
        for (let opcion of serviciosSelec.options) {
            ids.push(Number(opcion.value));
        }

        let datos = {
            nombre: form["nombre"].value.trim(),
            apellidos: form["apellidos"].value.trim(),
            email: form["email"].value.trim(),
            nacimiento: form["nacimiento"].value,
            contrasena: contrasena.value,
            servicios: ids
        };

        let respuesta = await fetch("/api/auth/registro", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(datos)
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