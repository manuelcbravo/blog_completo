const RAIZ = document.documentElement;
const CLAVE = 'tema-publico';

function aplicarTema(tema) {
    RAIZ.dataset.tema = tema;
    RAIZ.style.colorScheme = tema === 'claro' ? 'light' : 'dark';

    const boton = document.querySelector('[data-tema-toggle]');

    if (boton) {
        boton.textContent = tema === 'claro' ? '☾' : '☀';
        boton.setAttribute(
            'aria-label',
            tema === 'claro' ? 'Cambiar a tema oscuro' : 'Cambiar a tema claro',
        );
    }
}

function temaInicial() {
    const guardado = localStorage.getItem(CLAVE);

    if (guardado === 'claro' || guardado === 'oscuro') {
        return guardado;
    }

    return window.matchMedia('(prefers-color-scheme: light)').matches
        ? 'claro'
        : 'oscuro';
}

aplicarTema(temaInicial());

document.addEventListener('DOMContentLoaded', () => {
    aplicarTema(RAIZ.dataset.tema || temaInicial());

    const boton = document.querySelector('[data-tema-toggle]');

    boton?.addEventListener('click', () => {
        const siguiente = RAIZ.dataset.tema === 'claro' ? 'oscuro' : 'claro';
        localStorage.setItem(CLAVE, siguiente);
        aplicarTema(siguiente);
    });

    // Menús <details>: abren solos, pero cerrarlos al pulsar fuera, al elegir
    // una opción o con Escape es lo que la gente espera de un desplegable.
    const menus = document.querySelectorAll('details[data-menu]');

    menus.forEach((menu) => {
        menu.querySelectorAll('a').forEach((enlace) => {
            enlace.addEventListener('click', () => {
                menu.open = false;
            });
        });
    });

    document.addEventListener('click', (evento) => {
        menus.forEach((menu) => {
            if (menu.open && !menu.contains(evento.target)) {
                menu.open = false;
            }
        });
    });

    document.addEventListener('keydown', (evento) => {
        if (evento.key !== 'Escape') {
            return;
        }

        menus.forEach((menu) => {
            menu.open = false;
        });
    });

    const buscador = document.querySelector('[data-buscador]');
    buscador?.focus({ preventScroll: true });

    document.addEventListener('keydown', (evento) => {
        const esAtajo =
            (evento.metaKey || evento.ctrlKey) &&
            evento.key.toLowerCase() === 'k';

        if (!esAtajo) {
            return;
        }

        evento.preventDefault();

        if (buscador) {
            buscador.focus();
            buscador.select();

            return;
        }

        const enlace = document.querySelector('[data-ir-buscar]');
        window.location.href = enlace ? enlace.href : '/buscar';
    });
});
