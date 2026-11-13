<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendanceExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private Collection $asistencias
    ) {}

    public function collection(): Collection
    {
        return $this->asistencias;
    }

    public function headings(): array
    {
        return [
            'Practicante',
            'Fecha',
            'Hora de ingreso',
            'Hora de salida',
            'Tiempo trabajado',
            'Estado',
        ];
    }

    public function map($asistencia): array
    {
        return [
            trim(($asistencia->usuario->nombre ?? '').' '.($asistencia->usuario->apellidos ?? '')),
            $asistencia->fecha,
            $asistencia->hora_inicio instanceof \DateTimeInterface
                ? $asistencia->hora_inicio->format('H:i:s')
                : $asistencia->hora_inicio,
            $asistencia->hora_fin instanceof \DateTimeInterface
                ? $asistencia->hora_fin->format('H:i:s')
                : $asistencia->hora_fin,
            $asistencia->tiempo_trabajado instanceof \DateInterval
                ? $asistencia->tiempo_trabajado->format('%H:%I:%S')
                : $asistencia->tiempo_trabajado,
            $asistencia->tipo instanceof \BackedEnum
                ? $asistencia->tipo->value
                : $asistencia->tipo,
        ];
    }
}
