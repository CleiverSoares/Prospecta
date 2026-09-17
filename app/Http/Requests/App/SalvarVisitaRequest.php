<?php

namespace App\Http\Requests\App;

use App\Enums\StatusVisita;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalvarVisitaRequest extends FormRequest
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
        $feita = $this->input('status') === StatusVisita::Feita->value;

        return [
            'prospecto_id' => ['required', 'integer', 'exists:prospectos,id'],
            'status' => ['required', Rule::enum(StatusVisita::class)],
            'checkin_lat' => ['required', 'numeric'],
            'checkin_lng' => ['required', 'numeric'],
            'foto' => [$feita ? 'required' : 'nullable', 'file', 'image', 'max:8192'],
            'audio' => [$feita ? 'required' : 'nullable', 'file', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'checkin_lat.required' => 'GPS obrigatório para check-in.',
            'checkin_lng.required' => 'GPS obrigatório para check-in.',
            'foto.required' => 'Foto da fachada é obrigatória em visita feita.',
            'audio.required' => 'Áudio é obrigatório em visita feita.',
        ];
    }
}
