/**
 * Ray-casting: ponto [lng, lat] dentro de Polygon GeoJSON.
 */
export function pontoNoPoligono(lng, lat, poligono) {
    const ring = poligono?.coordinates?.[0];
    if (!Array.isArray(ring) || ring.length < 4) return false;

    let dentro = false;
    for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
        const xi = ring[i][0];
        const yi = ring[i][1];
        const xj = ring[j][0];
        const yj = ring[j][1];
        const intersect = ((yi > lat) !== (yj > lat))
            && (lng < ((xj - xi) * (lat - yi)) / ((yj - yi) || Number.EPSILON) + xi);
        if (intersect) dentro = !dentro;
    }

    return dentro;
}

export function resumirProspectosNaArea(prospectos, poligono) {
    const lista = Array.isArray(prospectos) ? prospectos : [];
    let total = 0;
    let clientes = 0;
    let leads = 0;

    lista.forEach((p) => {
        if (p.lat == null || p.lng == null) return;
        if (!pontoNoPoligono(p.lng, p.lat, poligono)) return;
        total += 1;
        if (p.is_cliente) clientes += 1;
        else leads += 1;
    });

    return { total, clientes, leads };
}

export function poligonoValidoDoDraw(draw) {
    const data = draw.getAll();
    const feature = data.features.find((f) => {
        const coords = f.geometry?.coordinates?.[0];
        return f.geometry?.type === 'Polygon'
            && Array.isArray(coords)
            && coords.length >= 4
            && coords.every((c) => Array.isArray(c) && Number.isFinite(c[0]) && Number.isFinite(c[1]));
    });

    return feature ? feature.geometry : null;
}
