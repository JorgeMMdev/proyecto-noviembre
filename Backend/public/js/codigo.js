(function () {
    let form = document.forms["verificarCodigo"];
    let errorBox = document.getElementById("codigoError");

    // Poner el cursor en la casilla al abrirse el modal
    setTimeout(function () {
        form["codigo"].focus();
    }, 0);

    form.onsubmit = async function (event) {
        event.preventDefault();
        errorBox.hidden = true;

        let respuesta = await fetch("/api/auth/verificar", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ codigo: form["codigo"].value.trim() })
        });
        let resultado = await respuesta.json();

        if (!respuesta.ok) {
            errorBox.textContent = resultado.error || "No se pudo verificar el código.";
            errorBox.hidden = false;
            form["codigo"].value = "";
            form["codigo"].focus();
            return;
        }

        // Código correcto: ya hay sesión, vamos al inicio
        location.href = "/";
    };
})();