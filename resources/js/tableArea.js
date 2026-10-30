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

const areaTable = document.querySelector('#areas-table');
new DataTable(areaTable,{
    serverSide: true,
    processing: false,
    pageLength: 10,
    ajax:areaTable.dataset.url,
    order: [[0, 'desc']],
    language: languages,
    columns: [
        { data: 'id' },
        { data: 'nombre' },
        {
            data: 'created_at',
            render: function (data, type, row) {
                if (!data) return '';

                return data.split('T')[0];
            }
        },
        { data: 'acciones', searchable:false, orderable: false },
    ],
});
