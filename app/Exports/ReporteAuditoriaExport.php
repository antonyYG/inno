<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ReporteAuditoriaExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        protected Collection $registros
    ) {}

    public function collection(): Collection
    {
        return $this->registros;
    }

    public function headings(): array
    {
        return [
            'Fecha de revisión',
            'Practicante',
            'Área',
            'Detalle',
            'Fecha de asistencia',
            'Administrador',
            'Acción',
            'Observación',
        ];
    }

    public function map($registro): array
    {
        return [
            $registro->fecha_revision,
            $registro->practicante,
            $registro->area,
            $registro->detalle,
            $registro->fecha_asistencia,
            $registro->administrador,
            $this->textoAccion($registro->accion),
            $registro->observacion,
        ];
    }

    private function textoAccion(mixed $accion): string
    {
        return match ($accion) {
            'aceptada' => 'Aceptada',
            'rechazada' => 'Rechazada',
            'actividad' => 'Registrada',
            default => 'No disponible',
        };
    }
}
