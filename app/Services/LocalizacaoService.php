<?php

namespace App\Services;

use App\Events\VendedorForaTerritorio;
use App\Models\Localizacao;
use App\Models\User;
use App\Repositories\LocalizacaoRepository;
use App\Support\GeoHelper;

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
        $localizacao = $this->localizacaoRepository->registrar([
            'user_id' => $usuario->id,
            'lat' => $dados['lat'],
            'lng' => $dados['lng'],
            'precisao' => $dados['precisao'] ?? null,
            'velocidade' => $dados['velocidade'] ?? null,
            'direcao' => $dados['direcao'] ?? null,
            'capturado_em' => now(),
        ]);

        $usuario->loadMissing('unidade');
        $poligono = $usuario->unidade?->poligono_geojson;
        if (is_array($poligono) && ($poligono['type'] ?? null) === 'Polygon') {
            $dentro = GeoHelper::pontoNoPoligono(
                (float) $localizacao->lng,
                (float) $localizacao->lat,
                $poligono,
            );
            if (! $dentro) {
                VendedorForaTerritorio::dispatch($usuario, $localizacao);
            }
        }

        return $localizacao;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function aoVivo(?int $unidadeId = null, ?int $gestorId = null, ?int $vendedorId = null, int $minutos = 15): array
    {
        $semSinalApos = (int) config('prospecta.tracking.alerta_sem_sinal_segundos', 180);
        $paradoAbaixo = (float) config('prospecta.tracking.alerta_parado_ms', 1.0);

        return $this->localizacaoRepository
            ->ultimasPorUsuario($minutos, $unidadeId, $gestorId, $vendedorId)
            ->map(function (Localizacao $loc) use ($semSinalApos, $paradoAbaixo) {
                $usuario = $loc->usuario;
                $unidade = $usuario?->unidade;
                $capturado = $loc->capturado_em;
                $idade = $capturado ? (int) $capturado->diffInSeconds(now()) : 0;

                $alertas = [];
                if ($idade >= $semSinalApos) {
                    $alertas[] = 'sem_sinal';
                }
                if ($loc->velocidade !== null && (float) $loc->velocidade < $paradoAbaixo && $idade < $semSinalApos) {
                    $alertas[] = 'parado';
                }

                $fora = false;
                $poligono = $unidade?->poligono_geojson;
                if (is_array($poligono) && ($poligono['type'] ?? null) === 'Polygon') {
                    $fora = ! GeoHelper::pontoNoPoligono((float) $loc->lng, (float) $loc->lat, $poligono);
                    if ($fora) {
                        $alertas[] = 'fora_territorio';
                    }
                }

                return [
                    'user_id' => $loc->user_id,
                    'nome' => $usuario?->name ?? 'Vendedor',
                    'unidade_id' => $usuario?->unidade_id,
                    'unidade_nome' => $unidade?->nome,
                    'unidade_tipo' => $unidade?->tipo?->value,
                    'foto_url' => $usuario?->foto_url,
                    'lat' => (float) $loc->lat,
                    'lng' => (float) $loc->lng,
                    'precisao' => $loc->precisao,
                    'velocidade' => $loc->velocidade,
                    'direcao' => $loc->direcao,
                    'capturado_em' => $capturado?->toIso8601String(),
                    'idade_segundos' => $idade,
                    'alertas' => $alertas,
                    'status' => $alertas[0] ?? 'ok',
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{lat: float, lng: float, capturado_em: string|null, velocidade: float|null}>
     */
    public function trajeto(int $userId, int $minutos = 120): array
    {
        return $this->localizacaoRepository
            ->historicoDoUsuario($userId, $minutos)
            ->map(fn (Localizacao $loc) => [
                'lat' => (float) $loc->lat,
                'lng' => (float) $loc->lng,
                'velocidade' => $loc->velocidade,
                'capturado_em' => $loc->capturado_em?->toIso8601String(),
            ])
            ->values()
            ->all();
    }
}
