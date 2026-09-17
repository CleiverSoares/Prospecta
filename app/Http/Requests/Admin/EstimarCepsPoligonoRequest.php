<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class EstimarCepsPoligonoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && ($user->can('unidades.criar') || $user->can('unidades.editar'));
    }

    public function rules(): array
    {
        return [
            'poligono_geojson' => ['required', 'array'],
            'poligono_geojson.type' => ['required', 'string'],
            'poligono_geojson.coordinates' => ['required', 'array', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'poligono_geojson.required' => 'Desenhe um polígono no mapa.',
        ];
    }
}
