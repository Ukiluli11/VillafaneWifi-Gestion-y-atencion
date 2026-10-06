const cuerpo = document.body;
const botonAbrir = document.querySelector('[data-abrir-menu]');
const raiz = document.documentElement;
const botonBarra = document.querySelector('[data-alternar-barra]');
const botonTema = document.querySelector('[data-alternar-tema]');

const guardarPreferencia = (clave, valor) => {
    try {
        localStorage.setItem(clave, valor);
    } catch (error) {
        // La preferencia se mantiene durante esta visita si el almacenamiento no está disponible.
    }
};

const actualizarBotonBarra = () => {
    const reducida = raiz.classList.contains('barra-reducida');
    const etiqueta = reducida ? 'Expandir barra lateral' : 'Contraer barra lateral';
    botonBarra?.setAttribute('aria-expanded', String(!reducida));
    botonBarra?.setAttribute('aria-label', etiqueta);
    botonBarra?.setAttribute('title', etiqueta);
};

const actualizarBotonTema = () => {
    const oscuro = raiz.classList.contains('tema-oscuro');
    const etiqueta = oscuro ? 'Activar modo claro' : 'Activar modo oscuro';
    botonTema?.setAttribute('aria-pressed', String(oscuro));
    botonTema?.setAttribute('aria-label', etiqueta);
    botonTema?.setAttribute('title', etiqueta);
};

botonBarra?.addEventListener('click', () => {
    raiz.classList.toggle('barra-reducida');
    guardarPreferencia('barra-panel', raiz.classList.contains('barra-reducida') ? 'reducida' : 'normal');
    actualizarBotonBarra();
});
botonTema?.addEventListener('click', () => {
    raiz.classList.toggle('tema-oscuro');
    guardarPreferencia('tema-panel', raiz.classList.contains('tema-oscuro') ? 'oscuro' : 'claro');
    actualizarBotonTema();
});
actualizarBotonBarra();
actualizarBotonTema();

document.querySelectorAll('.enlace-navegacion').forEach((enlace) => {
    enlace.title = enlace.textContent.trim();
});

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
