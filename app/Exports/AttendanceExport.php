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
        private Collection $registros
    ) {}

    public function collection(): Collection
    {
        return $this->registros;
    }

    public function headings(): array
    {
        return [
            'Practicante',
            'Área',
            'Fecha',
            'Hora de ingreso',
            'Hora de salida',
            'Tiempo trabajado',
            'Estado',
        ];
    }

    public function map($registro): array
    {
        return [
            $registro->practicante,
            $registro->area,
            $registro->fecha,
            $registro->hora_inicio,
            $registro->hora_fin,
            $registro->tiempo_trabajado,
            ucfirst($registro->estado),
        ];
    }
}
