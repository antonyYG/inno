<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EvidenceExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected Collection $evidencias
    ) {}

    public function collection(): Collection
    {
        return $this->evidencias;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Practicante',
            'Fecha',
            'Descripción',
            'Estado',
            'Observación',
        ];
    }

    public function map($evidencia): array
    {
        $usuario = $evidencia->asistencia?->usuario;

        return [
            $evidencia->id,
            $usuario
                ? $usuario->nombre.' '.$usuario->apellidos
                : 'Sin practicante',
            $evidencia->fecha,
            $evidencia->descripcion,
            $evidencia->estado,
            $evidencia->observacion,
        ];
    }
}
