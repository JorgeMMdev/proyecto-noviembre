window.addEventListener("load", function () {
    let pestanas = document.querySelectorAll(".sol-pestana");

    function mostrar(pestana) {
        for (let p of pestanas) {
            let activa = p === pestana;
            p.setAttribute("aria-selected", activa ? "true" : "false");
            document.getElementById(p.getAttribute("aria-controls")).hidden = !activa;
        }
        // Recordar la pestaña en la URL (#enviadas) para que sobreviva a una recarga
        history.replaceState(null, "", "#" + pestana.id.replace("tab-", ""));
    }

    for (let pestana of pestanas) {
        pestana.onclick = function () {
            mostrar(pestana);
        };
    }

    // Al cargar, abrir la pestaña que diga la URL
    let inicial = document.getElementById("tab-" + location.hash.slice(1));
    if (inicial) {
        mostrar(inicial);
    }
});