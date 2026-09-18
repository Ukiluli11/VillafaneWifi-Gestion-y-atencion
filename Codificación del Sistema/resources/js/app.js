const cuerpo = document.body;
const botonAbrir = document.querySelector('[data-abrir-menu]');
const botonAlternarLateral = document.querySelector('[data-alternar-lateral]');
const botonAlternarTema = document.querySelector('[data-alternar-tema]');

/** Aplica el tema elegido y mantiene accesible el estado del control. */
const aplicarTema = (tema) => {
    const oscuro = tema === 'oscuro';
    document.documentElement.dataset.tema = oscuro ? 'oscuro' : 'claro';
    botonAlternarTema?.setAttribute('aria-pressed', String(oscuro));
    botonAlternarTema?.setAttribute('aria-label', oscuro ? 'Activar modo claro' : 'Activar modo oscuro');
    botonAlternarTema?.setAttribute('title', oscuro ? 'Activar modo claro' : 'Activar modo oscuro');
};

/** Achica o despliega la barra lateral y recuerda la preferencia del usuario. */
const aplicarEstadoLateral = (colapsada) => {
    cuerpo.classList.toggle('lateral-colapsada', colapsada);
    document.documentElement.classList.remove('lateral-colapsada-inicial');
    botonAlternarLateral?.setAttribute('aria-pressed', String(colapsada));
    botonAlternarLateral?.setAttribute('aria-label', colapsada ? 'Desplegar barra lateral' : 'Achicar barra lateral');
    botonAlternarLateral?.setAttribute('title', colapsada ? 'Desplegar barra lateral' : 'Achicar barra lateral');
};

const alternarMenu = (abierto) => {
    cuerpo.classList.toggle('menu-abierto', abierto);
    botonAbrir?.setAttribute('aria-expanded', String(abierto));
};

botonAbrir?.addEventListener('click', () => alternarMenu(true));
botonAlternarLateral?.addEventListener('click', () => {
    const colapsada = !cuerpo.classList.contains('lateral-colapsada');
    localStorage.setItem('barra-lateral-colapsada', String(colapsada));
    aplicarEstadoLateral(colapsada);
});
botonAlternarTema?.addEventListener('click', () => {
    const tema = document.documentElement.dataset.tema === 'oscuro' ? 'claro' : 'oscuro';
    localStorage.setItem('tema-panel', tema);
    aplicarTema(tema);
});
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

aplicarTema(document.documentElement.dataset.tema);
aplicarEstadoLateral(localStorage.getItem('barra-lateral-colapsada') === 'true');
