<?php

namespace App\Support;

class GeoHelper
{
    /**
     * Ray-casting: ponto (lng, lat) dentro de Polygon GeoJSON.
     *
     * @param  array<string, mixed>  $poligono
     */
    public static function pontoNoPoligono(float $lng, float $lat, array $poligono): bool
    {
        $ring = $poligono['coordinates'][0] ?? null;

        if (! is_array($ring) || count($ring) < 4) {
            return false;
        }

        $dentro = false;
        $n = count($ring);

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = (float) ($ring[$i][0] ?? 0);
            $yi = (float) ($ring[$i][1] ?? 0);
            $xj = (float) ($ring[$j][0] ?? 0);
            $yj = (float) ($ring[$j][1] ?? 0);

            $intersect = (($yi > $lat) !== ($yj > $lat))
                && ($lng < (($xj - $xi) * ($lat - $yi)) / (($yj - $yi) ?: 1e-12) + $xi);

            if ($intersect) {
                $dentro = ! $dentro;
            }
        }

        return $dentro;
    }
}
