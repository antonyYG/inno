<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Auditoría general</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
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
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background-color: #020830;
            color: white;
            padding: 8px;
            text-align: left;
        }

        td {
            border: 1px solid #e2e8f0;
            padding: 7px;
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

    <h2>AUDITORÍA GENERAL</h2>

    <p class="subtitle">
        PractiControl - Fecha de generación: {{ now()->format('d/m/Y H:i') }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Fecha de revisión</th>
                <th>Practicante</th>
                <th>Área</th>
                <th>Detalle</th>
                <th>Fecha de asistencia</th>
                <th>Administrador</th>
                <th>Acción</th>
                <th>Observación</th>
            </tr>
        </thead>

        <tbody>
            @forelse($registros as $registro)
                <tr>
                    <td>{{ $registro->fecha_revision }}</td>
                    <td>{{ $registro->practicante }}</td>
                    <td>{{ $registro->area }}</td>
                    <td>{{ $registro->detalle }}</td>
                    <td>{{ $registro->fecha_asistencia }}</td>
                    <td>{{ $registro->administrador }}</td>
                    <td>{{ ['aceptada' => 'Aceptada', 'rechazada' => 'Rechazada', 'actividad' => 'Registrada'][$registro->accion] ?? 'No disponible' }}</td>
                    <td>{{ $registro->observacion }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center;">
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
