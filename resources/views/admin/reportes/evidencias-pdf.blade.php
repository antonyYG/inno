
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de evidencias</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 11px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 7px;
            text-align: left;
        }

        th {
            background: #eeeeee;
        }
    </style>
</head>
<body>
    <h2>Reporte de evidencias</h2>

    <p>Fecha de generación: {{ now()->format('d/m/Y H:i') }}</p>
    <p>Total de evidencias: {{ $evidencias->count() }}</p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Practicante</th>
                <th>Fecha</th>
                <th>Descripción</th>
                <th>Estado</th>
                <th>Observación</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($evidencias as $evidencia)
                <tr>
                    <td>{{ $evidencia->id }}</td>
                    <td>
                        {{ $evidencia->asistencia?->usuario
                            ? $evidencia->asistencia->usuario->nombre . ' ' . $evidencia->asistencia->usuario->apellidos
                            : 'Sin practicante' }}
                    </td>
                    <td>{{ $evidencia->fecha }}</td>
                    <td>{{ $evidencia->descripcion }}</td>
                    <td>{{ ucfirst($evidencia->estado) }}</td>
                    <td>{{ $evidencia->observacion ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No hay evidencias para mostrar.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
