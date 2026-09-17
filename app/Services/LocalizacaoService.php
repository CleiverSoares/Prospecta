<?php

namespace App\Services;

use App\Models\Localizacao;
use App\Models\User;
use App\Repositories\LocalizacaoRepository;
use Illuminate\Support\Collection;

class LocalizacaoService
{
    public function __construct(
        private readonly LocalizacaoRepository $localizacaoRepository,
    ) {}

    /**
     * @param  array{lat: float, lng: float, precisao?: float|null, velocidade?: float|null, direcao?: float|null}  $dados
     */
    public function registrar(User $usuario, array $dados): Localizacao
    {
        return $this->localizacaoRepository->registrar([
            'user_id' => $usuario->id,
            'lat' => $dados['lat'],
            'lng' => $dados['lng'],
            'precisao' => $dados['precisao'] ?? null,
            'velocidade' => $dados['velocidade'] ?? null,
            'direcao' => $dados['direcao'] ?? null,
            'capturado_em' => now(),
        ]);
    }

    /**
     * @return list<array{user_id: int, nome: string, lat: float, lng: float, precisao: float|null, velocidade: float|null, direcao: float|null, capturado_em: string, idade_segundos: int}>
     */
    public function aoVivo(?int $unidadeId = null, ?int $gestorId = null, ?int $vendedorId = null, int $minutos = 15): array
    {
        return $this->localizacaoRepository
            ->ultimasPorUsuario($minutos, $unidadeId, $gestorId, $vendedorId)
            ->map(function (Localizacao $loc) {
                $capturado = $loc->capturado_em;

                return [
                    'user_id' => $loc->user_id,
                    'nome' => $loc->usuario?->name ?? 'Vendedor',
                    'lat' => (float) $loc->lat,
                    'lng' => (float) $loc->lng,
                    'precisao' => $loc->precisao,
                    'velocidade' => $loc->velocidade,
                    'direcao' => $loc->direcao,
                    'capturado_em' => $capturado?->toIso8601String(),
                    'idade_segundos' => $capturado ? $capturado->diffInSeconds(now()) : 0,
                ];
            })
            ->values()
            ->all();
    }
}
