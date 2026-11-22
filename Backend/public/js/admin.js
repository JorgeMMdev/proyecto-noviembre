window.addEventListener("load", function () {

    // ---------- Pestañas ----------
    let pestanas = document.querySelectorAll(".adm-pestana");

    function mostrar(pestana) {
        for (let p of pestanas) {
            let activa = p === pestana;
            p.setAttribute("aria-selected", activa ? "true" : "false");
            document.getElementById(p.getAttribute("aria-controls")).hidden = !activa;
        }
        history.replaceState(null, "", "#" + pestana.id.replace("tab-", ""));
    }

    for (let pestana of pestanas) {
        pestana.onclick = function () {
            mostrar(pestana);
        };
    }

    let inicial = document.getElementById("tab-" + location.hash.slice(1));
    if (inicial) {
        mostrar(inicial);
    }

    // ---------- Utilidades ----------

    // Crea un elemento con su texto. Se usa textContent (y no innerHTML) para que,
    // si un nombre trae algo como <script>, se muestre como texto y no se ejecute.
    function crear(etiqueta, texto, clase) {
        let el = document.createElement(etiqueta);
        if (texto !== undefined) {
            el.textContent = texto;
        }
        if (clase) {
            el.className = clase;
        }
        return el;
    }

    // Botón pequeño de acción para las filas de la tabla
    function boton(texto, clase, alPulsar) {
        let b = crear("button", texto, "adm-boton" + (clase ? " " + clase : ""));
        b.type = "button";
        b.onclick = alPulsar;
        return b;
    }

    // Fila con un único mensaje que ocupa todas las columnas
    function filaMensaje(tbody, texto, columnas) {
        let fila = crear("tr");
        let celda = crear("td", texto, "adm-cargando");
        celda.colSpan = columnas;
        fila.appendChild(celda);
        tbody.appendChild(fila);
    }

    // Hace la petición y devuelve {ok, datos, mensaje}. Junta en un texto los errores del servidor.
    async function pedir(url, metodo, cuerpo) {
        let opciones = { method: metodo || "GET" };
        if (cuerpo !== undefined) {
            opciones.headers = { "Content-Type": "application/json" };
            opciones.body = JSON.stringify(cuerpo);
        }

        let respuesta = await fetch(url, opciones);
        let datos = await respuesta.json();

        let mensaje = null;
        if (!respuesta.ok) {
            mensaje = datos.errores ? Object.values(datos.errores).join(" ") : (datos.error || "Ha ocurrido un error.");
        }
        return { ok: respuesta.ok, datos: datos, mensaje: mensaje };
    }

    // Muestra u oculta el recuadro de error de un panel
    function mostrarError(caja, mensaje) {
        caja.textContent = mensaje || "";
        caja.hidden = !mensaje;
    }

    // Recarga las dos tablas: los cambios en una afectan a los números de la otra
    async function recargarTodo() {
        await cargarCategorias();
        await cargarServicios();
    }

    // ---------- Categorías ----------
    let filasCategorias = document.getElementById("filasCategorias");
    let errorCategorias = document.querySelector("#panel-categorias .adm-error");
    let listaCategorias = [];   // la usan también la tabla y el formulario de servicios

    async function cargarCategorias() {
        let resultado = await pedir("/api/categorias");
        listaCategorias = resultado.datos;
        filasCategorias.innerHTML = "";

        if (listaCategorias.length === 0) {
            filaMensaje(filasCategorias, "No hay categorías.", 4);
            return;
        }

        for (let c of listaCategorias) {
            let fila = crear("tr");
            fila.appendChild(crear("td", c.nombre, "adm-negrita"));
            fila.appendChild(crear("td", c.descripcion || "—"));
            fila.appendChild(crear("td", String(c.servicios)));

            let acciones = crear("td", undefined, "adm-acciones");
            acciones.append(
                boton("Editar", "", function () { abrirFormCategoria(c); }),
                boton("Borrar", "adm-boton--peligro", function () { borrarCategoria(c); })
            );
            fila.appendChild(acciones);

            filasCategorias.appendChild(fila);
        }
    }

    // Sin argumento: crear. Con una categoría: editarla.
    async function abrirFormCategoria(categoria) {
        let modal = new Modal(categoria ? "Editar categoría" : "Nueva categoría");
        await modal.cargarURL("/plantillas/formCategoria.html");
        modal.abrir();

        let form = document.forms["formCategoria"];
        let errorBox = document.getElementById("categoriaError");

        if (categoria) {
            form["nombre"].value = categoria.nombre;
            form["descripcion"].value = categoria.descripcion || "";
        }

        form.onsubmit = async function (event) {
            event.preventDefault();
            mostrarError(errorBox, null);

            let cuerpo = {
                nombre: form["nombre"].value.trim(),
                descripcion: form["descripcion"].value.trim()
            };
            let resultado = categoria
                ? await pedir("/api/categorias/" + categoria.id, "PUT", cuerpo)
                : await pedir("/api/categorias", "POST", cuerpo);

            if (!resultado.ok) {
                mostrarError(errorBox, resultado.mensaje);
                return;
            }

            // Sin recargar la página: se cierra el modal y se vuelven a pedir los datos
            modal.cerrarModal();
            recargarTodo();
        };
    }

    async function borrarCategoria(categoria) {
        if (!confirm("¿Borrar la categoría «" + categoria.nombre + "»?")) {
            return;
        }

        mostrarError(errorCategorias, null);
        let resultado = await pedir("/api/categorias/" + categoria.id, "DELETE");

        if (!resultado.ok) {
            mostrarError(errorCategorias, resultado.mensaje);
            return;
        }
        recargarTodo();
    }

    document.getElementById("nuevaCategoria").onclick = function () {
        abrirFormCategoria();
    };

    // ---------- Servicios ----------
    let filasServicios = document.getElementById("filasServicios");
    let errorServicios = document.querySelector("#panel-servicios .adm-error");

    function nombreCategoria(id) {
        let encontrada = listaCategorias.find(function (c) { return c.id === id; });
        return encontrada ? encontrada.nombre : "—";
    }

    // 1.5 → "1,5"
    function formatearHoras(horas) {
        return String(horas).replace(".", ",");
    }

    async function cargarServicios() {
        let resultado = await pedir("/api/servicios");
        filasServicios.innerHTML = "";

        if (resultado.datos.length === 0) {
            filaMensaje(filasServicios, "No hay servicios en el catálogo.", 6);
            return;
        }

        for (let s of resultado.datos) {
            let fila = crear("tr");
            fila.appendChild(crear("td", s.nombre, "adm-negrita"));
            fila.appendChild(crear("td", nombreCategoria(s.idCategoria)));
            fila.appendChild(crear("td", s.precio + " pts/h"));
            fila.appendChild(crear("td", formatearHoras(s.tiempo) + " h"));
            fila.appendChild(crear("td", String(s.ofertas)));

            let acciones = crear("td", undefined, "adm-acciones");
            acciones.append(
                boton("Editar", "", function () { abrirFormServicio(s); }),
                boton("Borrar", "adm-boton--peligro", function () { borrarServicio(s); })
            );
            fila.appendChild(acciones);

            filasServicios.appendChild(fila);
        }
    }

    // Sin argumento: crear. Con un servicio: editarlo.
    async function abrirFormServicio(servicio) {
        let modal = new Modal(servicio ? "Editar servicio" : "Nuevo servicio");
        await modal.cargarURL("/plantillas/formServicio.html");
        modal.abrir();

        let form = document.forms["formServicio"];
        let errorBox = document.getElementById("servicioError");

        // El desplegable se rellena con las categorías que ya tenemos cargadas
        for (let c of listaCategorias) {
            let opcion = crear("option", c.nombre);
            opcion.value = c.id;
            form["categoria"].appendChild(opcion);
        }

        if (servicio) {
            form["nombre"].value = servicio.nombre;
            form["categoria"].value = servicio.idCategoria;
            form["precio"].value = servicio.precio;
            form["tiempo"].value = servicio.tiempo;
            form["descripcion"].value = servicio.descripcion || "";
        }

        form.onsubmit = async function (event) {
            event.preventDefault();
            mostrarError(errorBox, null);

            let cuerpo = {
                nombre: form["nombre"].value.trim(),
                idCategoria: Number(form["categoria"].value),
                precio: Number(form["precio"].value),
                tiempo: Number(form["tiempo"].value),
                descripcion: form["descripcion"].value.trim()
            };
            let resultado = servicio
                ? await pedir("/api/servicios/" + servicio.id, "PUT", cuerpo)
                : await pedir("/api/servicios", "POST", cuerpo);

            if (!resultado.ok) {
                mostrarError(errorBox, resultado.mensaje);
                return;
            }

            modal.cerrarModal();
            recargarTodo();
        };
    }

    async function borrarServicio(servicio) {
        if (!confirm("¿Borrar el servicio «" + servicio.nombre + "»?")) {
            return;
        }

        mostrarError(errorServicios, null);
        let resultado = await pedir("/api/servicios/" + servicio.id, "DELETE");

        if (!resultado.ok) {
            mostrarError(errorServicios, resultado.mensaje);
            return;
        }
        recargarTodo();
    }

    document.getElementById("nuevoServicio").onclick = function () {
        abrirFormServicio();
    };

    // ---------- Arranque ----------
    recargarTodo();
});