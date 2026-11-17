
import DataTable from 'datatables.net';

const languages = {
    search: 'Buscar:',
    searchPlaceholder: 'Buscar...',
    processing: 'Procesando...',
    lengthMenu: 'Mostrar _MENU_ registros',
    info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
    infoEmpty: 'Mostrando 0 a 0 de 0 registros',
    infoFiltered: '(filtrado de _MAX_ registros en total)',
    zeroRecords: 'No se encontraron resultados',
    paginate: {
        first: 'Primero',
        last: 'Último',
        next: 'Siguiente',
        previous: 'Anterior',
    },
};

// Etiquetas de estados
function badgeEstado(estado) {
    const estados = {
        asistio: {
            texto: 'Asistió',
            clase: 'bg-green-100 text-green-700',
        },
        tardanza: {
            texto: 'Tardanza',
            clase: 'bg-yellow-100 text-yellow-700',
        },
        falto: {
            texto: 'Faltó',
            clase: 'bg-red-100 text-red-700',
        },
        justificado: {
            texto: 'Justificado',
            clase: 'bg-blue-100 text-blue-700',
        },
        pendiente: {
            texto: 'Pendiente',
            clase: 'bg-gray-100 text-gray-700',
        },
    };

    const resultado = estados[estado];

    if (!resultado) return '-';

    return `
        <span class="px-2.5 py-1 rounded-full text-xs font-semibold ${resultado.clase}">
            ${resultado.texto}
        </span>
    `;
}

// Etiquetas de acciones
function badgeAccion(accion) {
    const acciones = {
        evidencia_validada: {
            texto: 'Evidencia validada',
            clase: 'bg-green-100 text-green-700',
        },
        evidencia_rechazada: {
            texto: 'Evidencia rechazada',
            clase: 'bg-red-100 text-red-700',
        },
        justificacion_aprobada: {
            texto: 'Justificación aprobada',
            clase: 'bg-blue-100 text-blue-700',
        },
        justificacion_rechazada: {
            texto: 'Justificación rechazada',
            clase: 'bg-orange-100 text-orange-700',
        },
    };

    const resultado = acciones[accion];

    if (!resultado) {
        return accion ?? '-';
    }

    return `
        <span class="px-2.5 py-1 rounded-full text-xs font-semibold ${resultado.clase}">
            ${resultado.texto}
        </span>
    `;
}

const auditoriaTable = document.querySelector('#auditorias-table');

if (auditoriaTable) {
    new DataTable(auditoriaTable, {
        serverSide: true,
        pageLength: 10,
        ajax: auditoriaTable.dataset.url,
        language: languages,
        order: [[5, 'desc']],

        columns: [
            {
                data: 'administrador',
                orderable: false,
            },
            {
                data: 'accion',
                orderable: false,
                render: function (data) {
                    return badgeAccion(data);
                },
            },
            {
                data: 'practicante',
                orderable: false,
            },
            {
                data: 'estado_anterior',
                orderable: false,
                render: function (data) {
                    return badgeEstado(data);
                },
            },
            {
                data: 'estado_nuevo',
                orderable: false,
                render: function (data) {
                    return badgeEstado(data);
                },
            },
            {
                data: 'created_at',
                render: function (data) {
                    if (!data) return '-';

                    return new Date(data).toLocaleString('es-PE', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                    });
                },
            },
            {
                data: 'description',
                orderable: false,
            },
        ],
    });
}
