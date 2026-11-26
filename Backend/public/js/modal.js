function Modal(titulo) {

    this.velo = document.createElement("div");
    this.modal = document.createElement("div");
    this.velo.id = "velo";
    this.modal.id = "modal";

    this.titulo = document.createElement("div");
    this.titulo.className = "modal-cabecera";

    this.tituloTexto = document.createElement("div");
    this.tituloTexto.className = "modal-titulo";
    this.tituloTexto.textContent = titulo;

    this.cerrar = document.createElement("button");
    this.cerrar.className = "modal-cerrar";
    this.cerrar.type = "button";
    this.cerrar.setAttribute("aria-label", "Cerrar");
    this.cerrar.textContent = "×";
    this.cerrar.onclick = () => {
        this.cerrarModal();
    };

    this.contenido = document.createElement("div");
    this.contenido.className = "modal-cuerpo";

    this.codigo = document.createElement("script");

    this.titulo.appendChild(this.tituloTexto);
    this.titulo.appendChild(this.cerrar);
    this.modal.appendChild(this.titulo);
    this.modal.appendChild(this.contenido);
}

Modal.prototype.abrir = function () {

    if (document.body.modal) {
        document.body.modal.cerrarModal();
    }

    document.body.appendChild(this.velo);
    document.body.appendChild(this.modal);
    document.body.modal = this;

    document.body.style.overflow = "hidden";
    this.modal.tabIndex = -1;
    this.modal.focus();
};

Modal.prototype.cargarHTML = function (texto) {
    this.contenido.innerHTML = texto;
};

Modal.prototype.cargarURL = async function (url) {
    let peticion = await fetch(url);
    let contenido = await peticion.text();
    this.cargarHTML(contenido);
};

Modal.prototype.cargarLogica = async function (url) {
    let peticion = await fetch(url);
    let js = await peticion.text();
    this.codigo.textContent = js;
    this.contenido.appendChild(this.codigo);
};

Modal.prototype.cerrarModal = function () {
    this.velo.remove();
    this.modal.remove();
    document.body.style.overflow = "";
    document.body.modal = null;
};