<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

class ProspectarAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $uf = $this->input('uf');
        $this->merge([
            'bairro' => filled($this->input('bairro')) ? $this->input('bairro') : null,
            'cidade' => filled($this->input('cidade')) ? $this->input('cidade') : null,
            'cep' => filled($this->input('cep')) ? $this->input('cep') : null,
            'uf' => is_string($uf) && strlen(trim($uf)) === 2 ? strtoupper(trim($uf)) : null,
            'poligono' => filled($this->input('poligono')) ? $this->input('poligono') : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bairro' => ['nullable', 'string', 'max:120'],
            'cidade' => ['nullable', 'string', 'max:120'],
            'uf' => ['nullable', 'string', 'size:2'],
            'cep' => ['nullable', 'string', 'max:9'],
            'segmento' => ['nullable', 'string', 'max:40'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'raio_metros' => ['nullable', 'integer', 'min:200', 'max:5000'],
            'horas' => ['nullable', 'string', 'max:40'],
            'poligono' => ['nullable', 'array'],
            'poligono.type' => ['required_with:poligono', 'in:Polygon'],
            'poligono.coordinates' => ['required_with:poligono', 'array'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $temTexto = filled($this->input('bairro'))
                || filled($this->input('cep'))
                || filled($this->input('cidade'));
            $temPoly = filled($this->input('poligono'));
            $temCoords = $this->filled('lat') && $this->filled('lng');

            if (! $temTexto && ! $temPoly && ! $temCoords) {
                $validator->errors()->add('bairro', 'Informe o bairro/CEP ou desenhe no mapa.');
            }
        });
    }
}
