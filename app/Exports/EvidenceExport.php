<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EvidenceExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
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
            'ID',
            'Practicante',
            'Área',
            'Fecha de presentación',
            'Descripción',
            'Estado',
            'Observación',
        ];
    }

    public function map($registro): array
    {
        return [
            $registro->id,
            $registro->practicante,
            $registro->area,
            $registro->fecha,
            $registro->descripcion,
            ucfirst($registro->estado),
            $registro->observacion,
        ];
    }
}
