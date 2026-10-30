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
                'required_if:rol,practicing',
                ...($this->input('rol') === 'practicing'
                    ? [Rule::unique('usuarios', 'discord_id')]
                    : []),
            ],
            'area_id' => 'required_if:rol,practicing|exists:areas,id',
            'fecha_inicio' => 'nullable',
            'fecha_fin' => 'nullable',
        ];
    }
}
