// Abre en un modal los formularios del panel de admin.
// El formulario lo pinta Twig dentro de un <template> y se envía con un POST normal:
// el JS solo lo enseña en el modal; guardar y validar lo sigue haciendo PHP.
window.addEventListener("load", function () {

    function abrir(idPlantilla, titulo) {
        let plantilla = document.getElementById(idPlantilla);
        let modal = new Modal(titulo);
        modal.cargarHTML(plantilla.innerHTML);
        modal.abrir();

        // Poner el cursor en el primer campo
        let primerCampo = modal.contenido.querySelector("input, select, textarea");
        if (primerCampo) {
            primerCampo.focus();
        }
    }

    // Botones como: <button data-modal="tplNuevoServicio" data-titulo="Nuevo servicio">
    for (let boton of document.querySelectorAll("[data-modal]")) {
        boton.onclick = function () {
            abrir(boton.dataset.modal, boton.dataset.titulo);
        };
    }

    // Si PHP devolvió la página con errores, se vuelve a abrir el modal para verlos
    let conErrores = document.querySelector("template[data-abrir-al-cargar]");
    if (conErrores) {
        abrir(conErrores.id, conErrores.dataset.titulo);
    }
});