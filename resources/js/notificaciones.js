
const contenedor = document.querySelector('#admin-notificaciones');

if (contenedor) {
    const boton = document.querySelector('#btn-notificaciones');
    const panel = document.querySelector('#panel-notificaciones');
    const contador = document.querySelector('#contador-notificaciones');
    const lista = document.querySelector('#lista-notificaciones');

    const url = contenedor.dataset.url;
    const urlLeer = contenedor.dataset.readUrl;

    async function cargarNotificaciones() {
        try {
            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!response.ok) return;

            const resultado = await response.json();

            contador.textContent = resultado.contador;

            contador.classList.toggle(
                'hidden',
                resultado.contador === 0
            );

            contador.classList.toggle(
                'flex',
                resultado.contador > 0
            );

            lista.replaceChildren();

            if (resultado.notificaciones.length === 0) {
                lista.textContent = 'No tienes notificaciones.';
                lista.className += ' p-5 text-center text-sm text-gray-500';
                return;
            }

            resultado.notificaciones.forEach((notificacion) => {
                const elemento = document.createElement('button');

                elemento.type = 'button';
                elemento.className =
                    'w-full text-left p-4 border-b hover:bg-gray-50 transition ' +
                    (notificacion.leida ? 'bg-white' : 'bg-blue-50');

                const titulo = document.createElement('p');
                titulo.className = 'text-sm font-semibold text-gray-800';
                titulo.textContent = notificacion.titulo;

                const mensaje = document.createElement('p');
                mensaje.className = 'text-xs text-gray-600 mt-1';
                mensaje.textContent = notificacion.mensaje;

                const fecha = document.createElement('p');
                fecha.className = 'text-xs text-gray-400 mt-2';
                fecha.textContent = notificacion.fecha;

                elemento.append(titulo, mensaje, fecha);

                elemento.addEventListener('click', async () => {
                    if (!notificacion.leida) {
                        const response = await fetch(
                            urlLeer.replace(':id', notificacion.id),
                            {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN':
                                        document.querySelector(
                                            'meta[name="csrf-token"]'
                                        )?.content ?? '',
                                    Accept: 'application/json',
                                },
                            }
                        );

                        if (!response.ok) return;
                    }

                    await cargarNotificaciones();
                });

                lista.appendChild(elemento);
            });
        } catch (error) {
            console.error('Error al cargar notificaciones:', error);
        }
    }

    boton.addEventListener('click', () => {
        panel.classList.toggle('hidden');
        cargarNotificaciones();
    });

    document.addEventListener('click', (event) => {
        if (!contenedor.contains(event.target)) {
            panel.classList.add('hidden');
        }
    });

    cargarNotificaciones();

    // Actualizar el contador cada 15 segundos.
    setInterval(cargarNotificaciones, 15000);
}
