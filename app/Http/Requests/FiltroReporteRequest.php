<?php

namespace App\Http\Requests;

use App\Enum\EstadoAsistenciaEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FiltroReporteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $estados = $this->estadosPermitidos();

        return [
            'fechaDesde' => ['nullable', 'date_format:Y-m-d'],
            'fechaHasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:fechaDesde'],
            'practicante' => ['nullable', 'integer', 'exists:usuarios,id'],
            'administrador' => ['nullable', 'integer', 'exists:usuarios,id'],
            'estado' => ['nullable', Rule::in($estados)],
            'estadoAsistencia' => ['nullable', Rule::in($estados)],
            'accion' => ['nullable', Rule::in($estados)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function estadosPermitidos(): array
    {
        return match ($this->tipoReporte()) {
            'evidencias' => ['pendiente', 'aprobada', 'rechazada'],
            'auditorias' => ['aceptada', 'rechazada', 'actividad'],
            default => array_column(EstadoAsistenciaEnum::cases(), 'value'),
        };
    }

    private function tipoReporte(): string
    {
        $nombreRuta = (string) $this->route()?->getName();

        return match (true) {
            str_contains($nombreRuta, 'auditoria') => 'auditorias',
            str_contains($nombreRuta, 'evidencia') => 'evidencias',
            default => 'asistencias',
        };
    }
}
