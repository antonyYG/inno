<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de evidencias</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #334155;
        }

        h2 {
            text-align: center;
            color: #020830;
            margin-bottom: 5px;
        }

        .subtitle {
            text-align: center;
            color: #64748b;
            margin-bottom: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background-color: #020830;
            color: white;
            padding: 10px;
            text-align: left;
        }

        td {
            border: 1px solid #e2e8f0;
            padding: 9px;
            vertical-align: top;
        }

        tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .total {
            margin-top: 15px;
            text-align: right;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <h2>REPORTE DE EVIDENCIAS</h2>

    <p class="subtitle">
        PractiControl - Fecha de generación: {{ now()->format('d/m/Y H:i') }}
    </p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Practicante</th>
                <th>Área</th>
                <th>Fecha de presentación</th>
                <th>Descripción</th>
                <th>Estado</th>
                <th>Observación</th>
            </tr>
        </thead>

        <tbody>
            @forelse($registros as $registro)
                <tr>
                    <td>{{ $registro->id }}</td>
                    <td>{{ $registro->practicante }}</td>
                    <td>{{ $registro->area }}</td>
                    <td>{{ $registro->fecha }}</td>
                    <td>{{ $registro->descripcion }}</td>
                    <td>{{ ucfirst($registro->estado) }}</td>
                    <td>{{ $registro->observacion }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center;">
                        No se encontraron registros.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="total">
        Total de registros: {{ $registros->count() }}
    </p>

</body>
</html>
