/* TEMPORAL CHATGEPETADO*/


// Menú hamburguesa (solo visible en pantallas pequeñas)
const boton = document.querySelector('.menu-toggle');
const menu = document.getElementById('menu-principal');

if (boton && menu) {
    boton.addEventListener('click', () => {
        const abierto = menu.classList.toggle('abierto');
        boton.setAttribute('aria-expanded', String(abierto));
        boton.setAttribute('aria-label', abierto ? 'Cerrar menú' : 'Abrir menú');
    });

    // Cierra el menú con Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && menu.classList.contains('abierto')) {
            boton.click();
            boton.focus();
        }
    });
}