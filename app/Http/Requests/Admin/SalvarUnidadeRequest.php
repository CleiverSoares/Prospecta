<?php

namespace App\Http\Requests\Admin;

use App\Enums\TipoUnidade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalvarUnidadeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permissao = $this->routeIs('admin.unidades.store')
            ? 'unidades.criar'
            : 'unidades.editar';

        return $this->user()?->can($permissao) ?? false;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'tipo' => ['required', Rule::enum(TipoUnidade::class)],
            'cep_inicio' => ['nullable', 'string', 'max:9'],
            'cep_fim' => ['nullable', 'string', 'max:9'],
            'poligono_geojson' => ['nullable', 'array'],
        ];
    }
}
