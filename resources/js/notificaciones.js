const contenedor = document.querySelector('#admin-notificaciones');

if (contenedor) {
    const boton = document.querySelector('#btn-notificaciones');
    const panel = document.querySelector('#panel-notificaciones');
    const contador = document.querySelector('#contador-notificaciones');
    const contadorPendientes = document.querySelector('#contador-pendientes');
    const lista = document.querySelector('#lista-notificaciones');
    const pestanas = [...panel.querySelectorAll('[data-tab]')];

    const url = contenedor.dataset.url;
    const urlLeer = contenedor.dataset.readUrl;

    const clasesPestanaActiva = [
        'flex-1',
        'items-center',
        'justify-center',
        'gap-1.5',
        'rounded-lg',
        'bg-white',
        'px-3',
        'py-1.5',
        'text-xs',
        'font-semibold',
        'text-[#020830]',
        'shadow-sm',
        'transition',
    ];
    const clasesPestanaInactiva = [
        'flex-1',
        'items-center',
        'justify-center',
        'rounded-lg',
        'px-3',
        'py-1.5',
        'text-xs',
        'font-semibold',
        'text-slate-500',
        'transition',
        'hover:text-slate-800',
    ];

    let estadoActual = 'pendientes';
    let datos = {
        contador: 0,
        notificaciones: [],
        historial: [],
    };
    let temporizador = null;

    function notificacionesVisibles() {
        return estadoActual === 'pendientes' ? datos.notificaciones : datos.historial;
    }

    function actualizarContador() {
        const cantidad = Number(datos.contador) || 0;

        contador.textContent = cantidad > 99 ? '99+' : String(cantidad);
        contador.classList.toggle('hidden', cantidad === 0);
        contador.classList.toggle('flex', cantidad > 0);
        contadorPendientes.textContent = String(cantidad);
    }

    function actualizarPestanas() {
        pestanas.forEach((pestana) => {
            const activa = pestana.dataset.tab === estadoActual;

            pestana.className = activa
                ? clasesPestanaActiva.join(' ')
                : clasesPestanaInactiva.join(' ');
            pestana.setAttribute('aria-selected', activa ? 'true' : 'false');
        });

        lista.dataset.estado = estadoActual;
    }

    function crearIcono(notificacion, pendiente) {
        const contenedorIcono = document.createElement('span');
        contenedorIcono.className = pendiente
            ? 'flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#f2c94c]/15 text-[#8a6a00]'
            : 'flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500';

        const icono = document.createElement('i');
        icono.className = notificacion.icono || 'fa-solid fa-bell';
        icono.setAttribute('aria-hidden', 'true');

        contenedorIcono.appendChild(icono);

        return contenedorIcono;
    }

    function crearEstadoVacio() {
        const contenedorVacio = document.createElement('div');
        contenedorVacio.className = 'px-5 py-10 text-center';

        const iconoContenedor = document.createElement('div');
        iconoContenedor.className =
            'mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-400';

        const icono = document.createElement('i');
        icono.className = estadoActual === 'pendientes'
            ? 'fa-regular fa-bell-slash text-lg'
            : 'fa-regular fa-clock text-lg';
        icono.setAttribute('aria-hidden', 'true');

        iconoContenedor.appendChild(icono);

        const titulo = document.createElement('p');
        titulo.className = 'mt-3 text-sm font-semibold text-slate-700';
        titulo.textContent = estadoActual === 'pendientes' ? 'Todo al día' : 'Sin historial';

        const descripcion = document.createElement('p');
        descripcion.className = 'mt-1 text-xs leading-relaxed text-slate-500';
        descripcion.textContent = estadoActual === 'pendientes'
            ? 'No tienes notificaciones pendientes.'
            : 'Las notificaciones leídas aparecerán aquí.';

        contenedorVacio.append(iconoContenedor, titulo, descripcion);

        return contenedorVacio;
    }

    function crearElemento(notificacion) {
        const pendiente = !notificacion.leida;
        const elemento = document.createElement('button');

        elemento.type = 'button';
        elemento.dataset.id = notificacion.id;
        elemento.className = [
            'flex',
            'w-full',
            'gap-3',
            'border-b',
            'border-slate-100',
            'px-4',
            'py-3',
            'text-left',
            'transition',
            pendiente ? 'bg-[#f2c94c]/[0.07]' : 'bg-white',
            'hover:bg-slate-50',
        ].join(' ');

        const contenido = document.createElement('span');
        contenido.className = 'min-w-0 flex-1';

        const cabecera = document.createElement('span');
        cabecera.className = 'flex items-start justify-between gap-2';

        const titulo = document.createElement('span');
        titulo.className = 'truncate text-sm font-semibold text-slate-800';
        titulo.textContent = notificacion.titulo || 'Notificación';

        cabecera.appendChild(titulo);

        if (pendiente) {
            const punto = document.createElement('span');
            punto.className = 'mt-1.5 h-2 w-2 shrink-0 rounded-full bg-[#f2c94c] ring-2 ring-[#f2c94c]/20';
            punto.setAttribute('aria-label', 'Pendiente de lectura');
            cabecera.appendChild(punto);
        }

        const meta = document.createElement('span');
        meta.className = 'mt-0.5 block truncate text-[11px] font-semibold uppercase tracking-wide text-slate-400';
        meta.textContent = [
            notificacion.etiqueta || 'Sistema',
            notificacion.fecha_relativa || notificacion.fecha || '',
        ].filter(Boolean).join(' · ');

        const mensaje = document.createElement('span');
        mensaje.className = 'mt-1 line-clamp-2 block text-xs leading-relaxed text-slate-600';
        mensaje.textContent = notificacion.mensaje || '';

        const fecha = document.createElement('span');
        fecha.className = 'mt-1.5 flex items-center gap-1.5 text-[11px] text-slate-400';

        const iconoReloj = document.createElement('i');
        iconoReloj.className = 'fa-regular fa-clock';
        iconoReloj.setAttribute('aria-hidden', 'true');

        const textoFecha = document.createElement('span');
        textoFecha.textContent = [notificacion.fecha, notificacion.hora].filter(Boolean).join(' · ');

        fecha.append(iconoReloj, textoFecha);
        contenido.append(cabecera, meta, mensaje, fecha);
        elemento.append(crearIcono(notificacion, pendiente), contenido);

        elemento.addEventListener('click', () => abrir(notificacion, elemento));

        return elemento;
    }

    function render() {
        lista.replaceChildren();
        actualizarContador();
        actualizarPestanas();

        const visibles = notificacionesVisibles();

        if (visibles.length === 0) {
            lista.appendChild(crearEstadoVacio());

            return;
        }

        visibles.forEach((notificacion) => {
            lista.appendChild(crearElemento(notificacion));
        });
    }

    async function marcarLeida(notificacion) {
        const respuesta = await fetch(urlLeer.replace(':id', encodeURIComponent(notificacion.id)), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                Accept: 'application/json',
            },
        });

        if (!respuesta.ok) {
            throw new Error('No se pudo marcar la notificación como leída.');
        }

        return respuesta.json();
    }

    function sincronizarLectura(notificacion, resultado) {
        datos.contador = Number(resultado.contador ?? Math.max(datos.contador - 1, 0));
        datos.notificaciones = datos.notificaciones.filter(
            (item) => item.id !== notificacion.id
        );

        const leida = {
            ...notificacion,
            leida: true,
        };

        datos.historial = [leida, ...datos.historial.filter(
            (item) => item.id !== notificacion.id
        )].slice(0, 10);
    }

    async function abrir(notificacion, elemento) {
        if (elemento.dataset.procesando === 'true') {
            return;
        }

        elemento.dataset.procesando = 'true';

        try {
            if (!notificacion.leida) {
                const resultado = await marcarLeida(notificacion);

                sincronizarLectura(notificacion, resultado);
                render();
            }

            if (notificacion.url) {
                window.location.assign(notificacion.url);
            }
        } catch (error) {
            console.error('Error al abrir la notificación:', error);
            elemento.dataset.procesando = 'false';
        }
    }

    async function cargarNotificaciones() {
        try {
            const respuesta = await fetch(url, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!respuesta.ok) {
                return;
            }

            const resultado = await respuesta.json();

            datos = {
                contador: Number(resultado.contador) || 0,
                notificaciones: Array.isArray(resultado.notificaciones) ? resultado.notificaciones : [],
                historial: Array.isArray(resultado.historial) ? resultado.historial : [],
            };

            render();
        } catch (error) {
            console.error('Error al cargar notificaciones:', error);
        }
    }

    function abrirPanel() {
        const abierto = panel.classList.contains('hidden');

        panel.classList.toggle('hidden', !abierto);
        boton.setAttribute('aria-expanded', abierto ? 'true' : 'false');

        if (abierto) {
            cargarNotificaciones();

            if (temporizador === null) {
                temporizador = setInterval(cargarNotificaciones, 15000);
            }

            return;
        }

        if (temporizador !== null) {
            clearInterval(temporizador);
            temporizador = null;
        }
    }

    boton.addEventListener('click', abrirPanel);

    pestanas.forEach((pestana) => {
        pestana.addEventListener('click', () => {
            estadoActual = pestana.dataset.tab === 'historial' ? 'historial' : 'pendientes';
            render();
        });
    });

    document.addEventListener('click', (event) => {
        if (!contenedor.contains(event.target) && !panel.classList.contains('hidden')) {
            panel.classList.add('hidden');
            boton.setAttribute('aria-expanded', 'false');

            if (temporizador !== null) {
                clearInterval(temporizador);
                temporizador = null;
            }
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.classList.contains('hidden')) {
            panel.classList.add('hidden');
            boton.setAttribute('aria-expanded', 'false');
            boton.focus();
        }
    });

    cargarNotificaciones();
}
