window.addEventListener("load", function () {

    let btnRegistrar = document.getElementById("btnRegistrarse");

    if (btnRegistrar === null) {
        return;
    }

    btnRegistrar.onclick = async function (ev) {
        ev.preventDefault();

        let modal = new Modal("Registro de usuario");
        await modal.cargarURL("/plantillas/formRegistro.html");
        await modal.cargarLogica("/js/registro.js");
        modal.abrir();
    };

});