const cuerpo = document.body;
const botonAbrir = document.querySelector('[data-abrir-menu]');

const alternarMenu = (abierto) => {
    cuerpo.classList.toggle('menu-abierto', abierto);
    botonAbrir?.setAttribute('aria-expanded', String(abierto));
};

botonAbrir?.addEventListener('click', () => alternarMenu(true));
document.querySelectorAll('[data-cerrar-menu]').forEach((elemento) => {
    elemento.addEventListener('click', () => alternarMenu(false));
});
document.querySelectorAll('.enlace-navegacion').forEach((enlace) => {
    enlace.addEventListener('click', () => alternarMenu(false));
});
document.addEventListener('keydown', (evento) => {
    if (evento.key === 'Escape') {
        alternarMenu(false);
    }
});

document.querySelectorAll('form[data-confirmar]').forEach((formulario) => {
    formulario.addEventListener('submit', (evento) => {
        if (!window.confirm(formulario.dataset.confirmar)) {
            evento.preventDefault();
        }
    });
});

const selectorTipo = document.querySelector('[data-tipo-usuario]');
const camposEmpleado = document.querySelector('[data-campos-empleado]');

if (selectorTipo && camposEmpleado) {
    const actualizar = () => {
        camposEmpleado.hidden = selectorTipo.value !== 'empleado';
    };

    selectorTipo.addEventListener('change', actualizar);
    actualizar();
}
