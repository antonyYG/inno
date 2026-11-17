<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditUser extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'rol' => 'required',
            'email' => 'required|email|unique:usuarios,email,'.$this->user->id,
            'password' => 'nullable|string|min:8',
            'discord_id' => [
                // El rol real del sistema es 'practicante'.
                'required_if:rol,practicante',
                'nullable',
                ...($this->input('rol') === 'practicante'
                    ? [Rule::unique('usuarios', 'discord_id')->ignore($this->user->id)]
                    : []),
            ],
            'area_id' => [
                // El rol real del sistema es 'practicante'.
                'required_if:rol,practicante',
                'nullable',
                Rule::exists('areas', 'id'),
            ],
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'horas_requeridas' => 'nullable|integer|min:1',
        ];
    }

    /**
     * Mensajes de error comprensibles en español.
     */
    public function messages(): array
    {
        return [
            'discord_id.required_if' => 'El Discord ID es obligatorio para practicantes.',
            'discord_id.unique' => 'Este Discord ID ya está asociado a otro usuario.',
            'area_id.required_if' => 'El área es obligatoria para practicantes.',
            'area_id.exists' => 'El área seleccionada no es válida.',
            'fecha_fin.after_or_equal' => 'La fecha de finalización no puede ser anterior a la fecha de inicio.',
            'horas_requeridas.min' => 'Las horas requeridas deben ser un número entero positivo.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ];
    }

    /**
     * Preparar los datos para la validación.
     */
    protected function prepareForValidation(): void
    {
        // Un Discord ID vacío no debe bloquear la unicidad.
        if ($this->input('discord_id') === '') {
            $this->merge(['discord_id' => null]);
        }
    }
}
