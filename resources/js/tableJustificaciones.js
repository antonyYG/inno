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
}

const justificaionTable = document.querySelector('#justificaciones-table');
new DataTable(justificaionTable,{
    serverSide: true,
    processing: false,
    pageLength: 10,
    ajax:justificaionTable.dataset.url,
    language: languages,
    columns: [
        { data: 'id' },
        { data: 'fecha' },
        { data: 'usuario.nombre' },
        { data: 'tipo' },
        { data: 'acciones' },

    ],
});
