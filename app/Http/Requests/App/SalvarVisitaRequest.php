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
            'foto' => [
                $feita ? 'required' : 'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,heic,heif',
                'max:10240',
            ],
            'audio' => [
                $feita ? 'required' : 'nullable',
                'file',
                'mimetypes:audio/webm,audio/ogg,audio/mpeg,audio/mp4,audio/wav,audio/x-m4a,audio/m4a,audio/aac,video/webm',
                'max:10240',
            ],
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
            'foto.mimes' => 'Envie a foto como JPEG/PNG (tire de novo pela câmera do app).',
            'foto.max' => 'Foto muito grande (máx. 10 MB). Tire de novo pela câmera.',
            'audio.required' => 'Áudio é obrigatório em visita feita.',
            'audio.mimetypes' => 'Formato de áudio não aceito. Grave de novo no app.',
        ];
    }
}
