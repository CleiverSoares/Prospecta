<?php

namespace App\Http\Requests\Admin;

use App\Enums\TipoUnidade;
use App\Models\Unidade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalvarUnidadeRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->routeIs('admin.unidades.store')) {
            return $this->user()?->can('create', Unidade::class) ?? false;
        }

        /** @var Unidade|null $unidade */
        $unidade = $this->route('unidade');

        return $unidade instanceof Unidade
            && ($this->user()?->can('update', $unidade) ?? false);
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'tipo' => ['required', Rule::enum(TipoUnidade::class)],
            'cep_inicio' => ['nullable', 'string', 'max:9', 'regex:/^\d{5}-?\d{3}$/'],
            'cep_fim' => ['nullable', 'string', 'max:9', 'regex:/^\d{5}-?\d{3}$/', 'required_with:cep_inicio'],
            'poligono_geojson' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome da unidade.',
            'tipo.required' => 'Selecione o tipo da unidade.',
            'cep_inicio.regex' => 'CEP início inválido. Use 00000-000.',
            'cep_fim.regex' => 'CEP fim inválido. Use 00000-000.',
            'cep_fim.required_with' => 'Informe o CEP fim quando houver CEP início.',
        ];
    }
}
