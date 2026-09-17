<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SalvarUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $usuarioId = $this->route('usuario')?->id;

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:180',
                Rule::unique('users', 'email')->ignore($usuarioId),
            ],
            'password' => [
                $usuarioId ? 'nullable' : 'required',
                'string',
                Password::defaults(),
            ],
            'unidade_id' => ['nullable', 'integer', 'exists:unidades,id'],
            'gestor_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                Rule::notIn(array_filter([(int) $usuarioId])),
            ],
            'cep_base_inicio' => ['nullable', 'string', 'size:8'],
            'cep_base_fim' => ['nullable', 'string', 'size:8'],
            'origem_rotulo' => ['nullable', 'string', 'max:120'],
            'origem_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'origem_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'role' => ['required', 'string', Rule::in(['adm', 'gestor', 'vendedor'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cep_base_inicio' => $this->digitosCep($this->input('cep_base_inicio')),
            'cep_base_fim' => $this->digitosCep($this->input('cep_base_fim')),
            'unidade_id' => $this->filled('unidade_id') ? $this->input('unidade_id') : null,
            'gestor_id' => $this->filled('gestor_id') ? $this->input('gestor_id') : null,
        ]);
    }

    private function digitosCep(mixed $valor): ?string
    {
        if (! filled($valor)) {
            return null;
        }

        $digitos = preg_replace('/\D+/', '', (string) $valor) ?? '';

        return strlen($digitos) === 8 ? $digitos : $digitos;
    }
}
