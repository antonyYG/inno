// //
// import DataTable from 'datatables.net';
// // globales
// const languages = {
//     search: 'Buscar:',
//         searchPlaceholder: 'Buscar...',
//         processing: 'Procesando...',
//         lengthMenu: 'Mostrar _MENU_ registros',
//         info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
//         infoEmpty: 'Mostrando 0 a 0 de 0 registros',
//         infoFiltered: '(filtrado de _MAX_ registros en total)',
//         zeroRecords: 'No se encontraron resultados',
//         paginate: {
//             first: 'Primero',
//             last: 'Último',
//             next: 'Siguiente',
//             previous: 'Anterior',
//         },
// }

// // Usuarios
// const tableUsuario = document.querySelector('#users-table')
// const filtroRol = document.querySelector('#filter-rol');
// const tableUsuarios =new DataTable(tableUsuario, {
//     serverSide: true,
//     processing: false,
//     pageLength: 10,
//     ajax: {
//         url: tableUsuario.dataset.url,
//         data: function (d) {
//             d.rol = filtroRol.value;
//         },
//     },
//     language: languages,
//     columns: [
//         { data: 'id' },
//         { data: 'nombre' },
//         { data: 'email' },
//         { data: 'roles.0.name',searchable: false,orderable:false},
//         { data: 'acciones', orderable: false, searchable: false },
//     ],
// });

// filtroRol.addEventListener('change', function () {
//     tableUsuarios.ajax.reload();
// });


// const areaTable = document.querySelector('#areas-table');
// new DataTable(areaTable,{
//     serverSide: true,
//     processing: false,
//     pageLength: 10,
//     ajax:areaTable.dataset.url,
//     language: languages,
//     columns: [
//         { data: 'id' },
//         { data: 'nombre' },
//         { data: 'created_at' },
//     ],
// });